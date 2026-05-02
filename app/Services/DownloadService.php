<?php

namespace App\Services;


use App\Helpers\StringHelper;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Subscribers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DownloadService
{
    private const XLSX_EXT = 'xlsx';
    private const HEADER_FILL_COLOR = 'EE7171';
    private const SUMMARY_FILL_COLOR = 'EEEEEE';

    /**
     * @param int $id
     * @return Response|StreamedResponse
     */
    public function log(int $id): Response|StreamedResponse
    {
        $rowsExist = ReadySent::query()
            ->where('schedule_id', $id)
            ->exists();

        abort_if(!$rowsExist, 404);

        $stats = $this->buildLogStats($id);
        $filename = 'log' . date('d_m_Y') . '.xlsx';

        return $this->streamLogExcel($filename, $id, $stats);
    }

    /**
     * @param string $url
     * @return StreamedResponse
     */
    public function redirect(string $url): StreamedResponse
    {
        $decodedUrl = $this->decodeRouteBase64($url);
        $rowsExist = Redirect::query()
            ->where('url', $decodedUrl)
            ->exists();

        abort_if(!$rowsExist, 404);

        $filename = 'redirect_' . date('d_m_Y') . '.xlsx';

        return $this->streamXlsxFile($filename, fn (): string => $this->buildRedirectXlsxFile($decodedUrl));
    }

    /**
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
        if ($request->export_type === 'excel') {
            $filename = 'exportEmail_' . date('d_m_Y') . '.' . self::XLSX_EXT;

            if ($request->compress === 'zip') {
                return $this->zipFileResponse(
                    $filename,
                    fn (): string => $this->buildSubscribersXlsxFile($request->categoryId)
                );
            }

            return $this->streamXlsxFile(
                $filename,
                fn (): string => $this->buildSubscribersXlsxFile($request->categoryId)
            );
        }

        $subscribers = $this->getSubscribersList($request->categoryId);
        [$contents, $ext] = $this->buildSubscribersFile($subscribers, $request->export_type);

        $filename = 'exportEmail_' . date('d_m_Y') . '.' . $ext;

        if ($request->compress === 'zip') {
            return $this->zipResponse($contents, $filename);
        }

        return response($contents, 200, [
            'Content-Disposition' => "attachment; filename={$filename}",
            'Content-Type' => StringHelper::getMimeType($ext),
        ]);
    }

    /**
     * @param int $scheduleId
     * @return array
     */
    private function buildLogStats(int $scheduleId): array
    {
        $total = ReadySent::query()
            ->where('schedule_id', $scheduleId)
            ->count();

        $failedCount = ReadySent::query()
            ->where('schedule_id', $scheduleId)
            ->where('success', 0)
            ->count();

        $readCount = ReadySent::query()
            ->where('schedule_id', $scheduleId)
            ->where('readMail', 1)
            ->count();

        $timeInfo = ReadySent::query()
            ->selectRaw('sec_to_time(UNIX_TIMESTAMP(max(created_at)) - UNIX_TIMESTAMP(min(created_at))) as totaltime')
            ->where('schedule_id', $scheduleId)
            ->first();

        $successCount = max($total - $failedCount, 0);
        $successPercent = $total > 0 ? (100 * $successCount / $total) : 0;

        return [
            'total' => $total,
            'read' => $readCount,
            'spent_time' => $timeInfo->totaltime ?? '',
            'success_percent' => $successPercent,
        ];
    }

    /**
     * Stream a memory-safe XLSX delivery report for large mailing logs.
     *
     * @param string $filename
     * @param int $scheduleId
     * @param array $stats
     * @return StreamedResponse
     */
    private function streamLogExcel(string $filename, int $scheduleId, array $stats): StreamedResponse
    {
        return $this->streamXlsxFile($filename, fn (): string => $this->buildLogXlsxFile($scheduleId, $stats));
    }

    /**
     * Stream a generated XLSX file and remove the temporary file afterwards.
     *
     * @param string $filename
     * @param callable $buildFile
     * @return StreamedResponse
     */
    private function streamXlsxFile(string $filename, callable $buildFile): StreamedResponse
    {
        return response()->streamDownload(function () use ($buildFile): void {
            $xlsxFile = $buildFile();

            try {
                readfile($xlsxFile);
            } finally {
                @unlink($xlsxFile);
            }
        }, $filename, [
            'Cache-Control' => 'max-age=0',
            'Content-Type' => StringHelper::getMimeType(self::XLSX_EXT),
        ]);
    }

    /**
     * Build an XLSX file by writing worksheet XML incrementally instead of keeping cells in memory.
     *
     * @param int $scheduleId
     * @param array $stats
     * @return string
     */
    private function buildLogXlsxFile(int $scheduleId, array $stats): string
    {
        return $this->buildXlsxFile(
            function ($sheet) use ($scheduleId, $stats): void {
                $this->writeLogWorksheet($sheet, $scheduleId, $stats);
            },
            'Log'
        );
    }

    /**
     * Build a memory-safe XLSX redirect report.
     *
     * @param string $url
     * @return string
     */
    private function buildRedirectXlsxFile(string $url): string
    {
        return $this->buildXlsxFile(
            function ($sheet) use ($url): void {
                $this->writeRedirectWorksheet($sheet, $url);
            },
            'Redirect'
        );
    }

    /**
     * Build a memory-safe XLSX subscriber export.
     *
     * @param array|null $categoryIds
     * @return string
     */
    private function buildSubscribersXlsxFile(?array $categoryIds): string
    {
        return $this->buildXlsxFile(
            function ($sheet) use ($categoryIds): void {
                $this->writeSubscribersWorksheet($sheet, $categoryIds);
            },
            'Subscribers'
        );
    }

    /**
     * Build an XLSX file by writing worksheet XML incrementally instead of keeping cells in memory.
     *
     * @param callable $writeWorksheet
     * @param string $sheetName
     * @return string
     */
    private function buildXlsxFile(callable $writeWorksheet, string $sheetName): string
    {
        $sheetFile = $this->createTempFile('sheet');
        $xlsxFile = $this->createTempFile('xlsx');

        $sheet = fopen($sheetFile, 'wb');

        if ($sheet === false) {
            @unlink($sheetFile);
            @unlink($xlsxFile);
            throw new \RuntimeException('Failed to create temporary worksheet file.');
        }

        try {
            $writeWorksheet($sheet);
        } finally {
            fclose($sheet);
        }

        $zip = new ZipArchive();

        if ($zip->open($xlsxFile, ZipArchive::OVERWRITE) !== true) {
            @unlink($sheetFile);
            @unlink($xlsxFile);
            throw new \RuntimeException('Failed to create XLSX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCorePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFile($sheetFile, 'xl/worksheets/sheet1.xml');
        $zip->close();

        @unlink($sheetFile);

        return $xlsxFile;
    }

    /**
     * Write the delivery report worksheet XML one row at a time.
     *
     * @param resource $sheet
     * @param int $scheduleId
     * @param array $stats
     * @return void
     */
    private function writeLogWorksheet($sheet, int $scheduleId, array $stats): void
    {
        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<dimension ref="A1:F' . ($stats['total'] + 2) . '"/>');
        fwrite($sheet, '<cols><col min="1" max="1" width="30" customWidth="1"/><col min="2" max="2" width="25" customWidth="1"/><col min="3" max="3" width="15" customWidth="1"/><col min="4" max="4" width="15" customWidth="1"/><col min="5" max="5" width="10" customWidth="1"/><col min="6" max="6" width="35" customWidth="1"/></cols>');
        fwrite($sheet, '<sheetData>');

        $summary = __('frontend.str.total') . ': ' . $stats['total'] . "\n"
            . __('frontend.str.sent') . ': ' . $stats['success_percent'] . "%\n"
            . __('frontend.str.spent_time') . ': ' . $stats['spent_time'] . "\n"
            . __('frontend.str.read') . ': ' . $stats['read'];

        fwrite($sheet, '<row r="1" ht="70" customHeight="1">');
        $this->writeInlineCell($sheet, 'A', 1, $summary, 1);
        fwrite($sheet, '</row>');

        fwrite($sheet, '<row r="2">');
        $this->writeInlineCell($sheet, 'A', 2, __('frontend.str.newsletter'), 2);
        $this->writeInlineCell($sheet, 'B', 2, __('frontend.str.email'), 2);
        $this->writeInlineCell($sheet, 'C', 2, __('frontend.str.time'), 2);
        $this->writeInlineCell($sheet, 'D', 2, __('frontend.str.status'), 2);
        $this->writeInlineCell($sheet, 'E', 2, __('frontend.str.read'), 2);
        $this->writeInlineCell($sheet, 'F', 2, __('frontend.str.error'), 2);
        fwrite($sheet, '</row>');

        $rowIndex = 2;

        ReadySent::query()
            ->select(['id', 'template', 'email', 'created_at', 'success', 'readMail', 'errorMsg'])
            ->where('schedule_id', $scheduleId)
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use ($sheet, &$rowIndex): void {
                foreach ($rows as $row) {
                    $rowIndex++;

                    fwrite($sheet, '<row r="' . $rowIndex . '">');
                    $this->writeInlineCell($sheet, 'A', $rowIndex, (string) $row->template);
                    $this->writeInlineCell($sheet, 'B', $rowIndex, (string) $row->email);
                    $this->writeInlineCell($sheet, 'C', $rowIndex, (string) $row->created_at);
                    $this->writeInlineCell(
                        $sheet,
                        'D',
                        $rowIndex,
                        $row->success == 1 ? __('frontend.str.send_status_yes') : __('frontend.str.send_status_no'),
                        3
                    );
                    $this->writeInlineCell(
                        $sheet,
                        'E',
                        $rowIndex,
                        $row->readMail == 1 ? __('frontend.str.yes') : __('frontend.str.no'),
                        3
                    );
                    $this->writeInlineCell($sheet, 'F', $rowIndex, (string) $row->errorMsg);
                    fwrite($sheet, '</row>');
                }
            });

        fwrite($sheet, '</sheetData>');
        fwrite($sheet, '<mergeCells count="1"><mergeCell ref="A1:F1"/></mergeCells>');
        fwrite($sheet, '</worksheet>');
    }

    /**
     * Write redirect tracking rows to worksheet XML in chunks.
     *
     * @param resource $sheet
     * @param string $url
     * @return void
     */
    private function writeRedirectWorksheet($sheet, string $url): void
    {
        $total = Redirect::query()
            ->where('url', $url)
            ->count();

        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<dimension ref="A1:B' . ($total + 1) . '"/>');
        fwrite($sheet, '<cols><col min="1" max="1" width="35" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/></cols>');
        fwrite($sheet, '<sheetData>');

        fwrite($sheet, '<row r="1">');
        $this->writeInlineCell($sheet, 'A', 1, 'Email', 2);
        $this->writeInlineCell($sheet, 'B', 1, 'Time', 2);
        fwrite($sheet, '</row>');

        $rowIndex = 1;

        Redirect::query()
            ->select(['id', 'email', 'created_at'])
            ->where('url', $url)
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use ($sheet, &$rowIndex): void {
                foreach ($rows as $row) {
                    $rowIndex++;

                    fwrite($sheet, '<row r="' . $rowIndex . '">');
                    $this->writeInlineCell($sheet, 'A', $rowIndex, (string) $row->email);
                    $this->writeInlineCell($sheet, 'B', $rowIndex, (string) $row->created_at);
                    fwrite($sheet, '</row>');
                }
            });

        fwrite($sheet, '</sheetData>');
        fwrite($sheet, '</worksheet>');
    }

    /**
     * Write subscriber export rows to worksheet XML in chunks.
     *
     * @param resource $sheet
     * @param array|null $categoryIds
     * @return void
     */
    private function writeSubscribersWorksheet($sheet, ?array $categoryIds): void
    {
        $query = $this->getSubscribersQuery($categoryIds);
        $total = (clone $query)->count();

        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<dimension ref="A1:B' . ($total + 1) . '"/>');
        fwrite($sheet, '<cols><col min="1" max="1" width="35" customWidth="1"/><col min="2" max="2" width="30" customWidth="1"/></cols>');
        fwrite($sheet, '<sheetData>');

        fwrite($sheet, '<row r="1">');
        $this->writeInlineCell($sheet, 'A', 1, 'Email', 2);
        $this->writeInlineCell($sheet, 'B', 1, 'Name', 2);
        fwrite($sheet, '</row>');

        $rowIndex = 1;

        (clone $query)
            ->orderBy('subscribers.id')
            ->chunk(2000, function ($subscribers) use ($sheet, &$rowIndex): void {
                foreach ($subscribers as $subscriber) {
                    $rowIndex++;

                    fwrite($sheet, '<row r="' . $rowIndex . '">');
                    $this->writeInlineCell($sheet, 'A', $rowIndex, (string) $subscriber->email);
                    $this->writeInlineCell($sheet, 'B', $rowIndex, (string) $subscriber->name);
                    fwrite($sheet, '</row>');
                }
            });

        fwrite($sheet, '</sheetData>');
        fwrite($sheet, '</worksheet>');
    }

    /**
     * Write one inline string cell to the worksheet XML.
     *
     * @param resource $sheet
     * @param string $column
     * @param int $row
     * @param string $value
     * @param int|null $style
     * @return void
     */
    private function writeInlineCell($sheet, string $column, int $row, string $value, ?int $style = null): void
    {
        $styleAttribute = $style !== null ? ' s="' . $style . '"' : '';
        $spaceAttribute = preg_match('/^\s|\s$/u', $value) ? ' xml:space="preserve"' : '';

        fwrite(
            $sheet,
            '<c r="' . $column . $row . '"' . $styleAttribute . ' t="inlineStr"><is><t' . $spaceAttribute . '>'
            . $this->xmlEscape($value)
            . '</t></is></c>'
        );
    }

    /**
     * Create a temporary file path and reserve it for writing.
     *
     * @param string $prefix
     * @return string
     */
    private function createTempFile(string $prefix): string
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);

        if ($file === false) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        return $file;
    }

    /**
     * Escape text for safe XML output.
     *
     * @param string $value
     * @return string
     */
    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * Decode a URL-safe or regular base64 route parameter.
     *
     * @param string $value
     * @return string
     */
    private function decodeRouteBase64(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($normalized, true) ?: '';
    }

    /**
     * Return the XLSX content type manifest.
     *
     * @return string
     */
    private function xlsxContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    /**
     * Return root package relationships for the XLSX archive.
     *
     * @return string
     */
    private function xlsxRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    /**
     * Return workbook XML for a single-sheet XLSX archive.
     *
     * @return string
     */
    private function xlsxWorkbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->xmlEscape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    /**
     * Return workbook relationships for the worksheet and styles.
     *
     * @return string
     */
    private function xlsxWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /**
     * Return minimal XLSX style definitions used by the log report.
     *
     * @return string
     */
    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEEEEE"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEE7171"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="2" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * Return core document properties for the XLSX archive.
     *
     * @return string
     */
    private function xlsxCorePropertiesXml(): string
    {
        $created = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Alexander Yanitsky</dc:creator>'
            . '<cp:lastModifiedBy>PHP Newsletter</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    /**
     * Return extended document properties for the XLSX archive.
     *
     * @return string
     */
    private function xlsxAppPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>PHP Newsletter</Application>'
            . '</Properties>';
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @return void
     */
    private function configureLogProperties(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->getProperties()
            ->setCreator('Alexander Yanitsky')
            ->setLastModifiedBy('PHP Newsletter')
            ->setTitle(__('frontend.str.log'))
            ->setSubject('Office 2007 XLSX Document')
            ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Log file');
    }

    /**
     * @param $sheet
     * @param array $stats
     * @return void
     */
    private function fillLogHeader($sheet, array $stats): void
    {
        $summary = __('frontend.str.total') . ': ' . $stats['total'] . "\n"
            . __('frontend.str.sent') . ': ' . $stats['success_percent'] . "%\n"
            . __('frontend.str.spent_time') . ': ' . $stats['spent_time'] . "\n"
            . __('frontend.str.read') . ': ' . $stats['read'];

        $sheet
            ->setCellValue('A1', $summary)
            ->setCellValue('A2', __('frontend.str.newsletter'))
            ->setCellValue('B2', __('frontend.str.email'))
            ->setCellValue('C2', __('frontend.str.time'))
            ->setCellValue('D2', __('frontend.str.status'))
            ->setCellValue('E2', __('frontend.str.read'))
            ->setCellValue('F2', __('frontend.str.error'));

        $sheet->mergeCells('A1:F1');
    }

    /**
     * @param $sheet
     * @param Collection $rows
     * @return void
     */
    private function fillLogRows($sheet, Collection $rows): void
    {
        $rowIndex = 2;

        foreach ($rows as $row) {
            $rowIndex++;

            $sheet
                ->setCellValue('A' . $rowIndex, $row->template)
                ->setCellValue('B' . $rowIndex, $row->email)
                ->setCellValue('C' . $rowIndex, $row->created_at)
                ->setCellValue(
                    'D' . $rowIndex,
                    $row->success == 1
                        ? __('frontend.str.send_status_yes')
                        : __('frontend.str.send_status_no')
                )
                ->setCellValue(
                    'E' . $rowIndex,
                    $row->readMail == 1
                        ? __('frontend.str.yes')
                        : __('frontend.str.no')
                )
                ->setCellValue('F' . $rowIndex, $row->errorMsg);

            $this->setHorizontalCenter($sheet, ['D' . $rowIndex, 'E' . $rowIndex]);
        }
    }

    /**
     * @param $sheet
     * @return void
     */
    private function formatLogSheet($sheet): void
    {
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $this->applyFill($sheet, 'A1', self::SUMMARY_FILL_COLOR);

        $headerCells = ['A2', 'B2', 'C2', 'D2', 'E2', 'F2'];

        foreach ($headerCells as $cell) {
            $this->applyFill($sheet, $cell, self::HEADER_FILL_COLOR);
        }

        $this->setHorizontalCenter($sheet, $headerCells);

        $sheet->getRowDimension(1)->setRowHeight(70);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(35);
    }

    /**
     * @param $sheet
     * @param string $cell
     * @param string $rgb
     * @return void
     */
    private function applyFill($sheet, string $cell, string $rgb): void
    {
        $sheet->getStyle($cell)->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $rgb],
        ]);
    }

    /**
     * @param $sheet
     * @param array $cells
     * @return void
     */
    private function setHorizontalCenter($sheet, array $cells): void
    {
        foreach ($cells as $cell) {
            $sheet->getStyle($cell)->getAlignment()->applyFromArray([
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]);
        }
    }

    /**
     * @param Collection $subscribers
     * @param string $type
     * @return array|string[]
     */
    private function buildSubscribersFile(Collection $subscribers, string $type): array
    {
        if ($type === 'text') {
            $contents = '';

            foreach ($subscribers as $subscriber) {
                $contents .= "{$subscriber->email} {$subscriber->name}\n";
            }

            return [$contents, 'txt'];
        }

        if ($type === 'excel') {
            $contents = $this->renderExcel(function (Spreadsheet $spreadsheet) use ($subscribers): void {
                $sheet = $spreadsheet->getActiveSheet();

                $sheet
                    ->setCellValue('A1', 'Email')
                    ->setCellValue('B1', 'Name');

                $rowIndex = 1;

                foreach ($subscribers as $subscriber) {
                    $rowIndex++;

                    $sheet
                        ->setCellValue('A' . $rowIndex, $subscriber->email)
                        ->setCellValue('B' . $rowIndex, $subscriber->name);
                }
            });

            return [$contents, self::XLSX_EXT];
        }

        throw new InvalidArgumentException('Invalid export type');
    }

    /**
     * @param string $contents
     * @param string $innerFilename
     * @return StreamedResponse
     */
    private function zipResponse(string $contents, string $innerFilename): StreamedResponse
    {
        $zipFilename = pathinfo($innerFilename, PATHINFO_FILENAME) . '.zip';

        return response()->streamDownload(function () use ($contents, $innerFilename): void {
            $zip = new ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');

            if ($tmpFile === false) {
                throw new \RuntimeException('Failed to create temporary file.');
            }

            if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
                @unlink($tmpFile);
                throw new \RuntimeException('Failed to create zip archive.');
            }

            $zip->addFromString($innerFilename, $contents);
            $zip->close();

            readfile($tmpFile);
            @unlink($tmpFile);
        }, $zipFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Stream a zip archive containing a generated temporary file.
     *
     * @param string $innerFilename
     * @param callable $buildFile
     * @return StreamedResponse
     */
    private function zipFileResponse(string $innerFilename, callable $buildFile): StreamedResponse
    {
        $zipFilename = pathinfo($innerFilename, PATHINFO_FILENAME) . '.zip';

        return response()->streamDownload(function () use ($innerFilename, $buildFile): void {
            $sourceFile = $buildFile();
            $zip = new ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');

            if ($tmpFile === false) {
                @unlink($sourceFile);
                throw new \RuntimeException('Failed to create temporary file.');
            }

            if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
                @unlink($sourceFile);
                @unlink($tmpFile);
                throw new \RuntimeException('Failed to create zip archive.');
            }

            $zip->addFile($sourceFile, $innerFilename);
            $zip->close();

            try {
                readfile($tmpFile);
            } finally {
                @unlink($sourceFile);
                @unlink($tmpFile);
            }
        }, $zipFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @param string $filename
     * @param callable $callback
     * @return StreamedResponse
     */
    private function streamExcel(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback): void {
            $spreadsheet = new Spreadsheet();
            $callback($spreadsheet);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => StringHelper::getMimeType(self::XLSX_EXT),
        ]);
    }

    /**
     * @param string $filename
     * @param callable $callback
     * @return Response
     */
    private function excelResponse(string $filename, callable $callback): Response
    {
        $contents = $this->renderExcel($callback);

        return response($contents, 200, [
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Cache-Control' => 'max-age=0',
            'Content-Type' => StringHelper::getMimeType(self::XLSX_EXT),
        ]);
    }

    /**
     * @param callable $callback
     * @return string
     */
    private function renderExcel(callable $callback): string
    {
        $spreadsheet = new Spreadsheet();
        $callback($spreadsheet);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * @param array|null $ids
     * @return Collection
     */
    private function getSubscribersList(?array $ids): Collection
    {
        return $this->getSubscribersQuery($ids)->get();
    }

    /**
     * Build the active subscribers query used by text and XLSX exports.
     *
     * @param array|null $ids
     * @return Builder
     */
    private function getSubscribersQuery(?array $ids): Builder
    {
        if ($ids) {
            return Subscribers::query()
                ->select('subscribers.id', 'subscribers.name', 'subscribers.email')
                ->distinct()
                ->leftJoin('subscriptions', function ($join) use ($ids) {
                    $join->on('subscribers.id', '=', 'subscriptions.subscriber_id')
                        ->whereIn('subscriptions.category_id', $ids);
                })
                ->where('subscribers.active', 1)
                ->whereNotNull('subscriptions.subscriber_id');
        }

        return Subscribers::query()
            ->select('subscribers.id', 'subscribers.name', 'subscribers.email')
            ->where('active', 1)
            ->orderBy('subscribers.id');
    }
}
