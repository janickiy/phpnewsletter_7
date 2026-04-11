<?php

namespace App\Services;


use App\Helpers\StringHelper;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Subscribers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @return Response
     */
    public function log(int $id): Response
    {
        $rows = ReadySent::query()
            ->where('schedule_id', $id)
            ->get();

        abort_if($rows->isEmpty(), 404);

        $stats = $this->buildLogStats($id, $rows);
        $filename = 'log' . date('d_m_Y') . '.xlsx';

        return $this->excelResponse(
            $filename,
            function (Spreadsheet $spreadsheet) use ($rows, $stats): void {
                $sheet = $spreadsheet->getActiveSheet();

                $this->configureLogProperties($spreadsheet);
                $this->fillLogHeader($sheet, $stats);
                $this->fillLogRows($sheet, $rows);
                $this->formatLogSheet($sheet);
            }
        );
    }

    /**
     * @param string $url
     * @return StreamedResponse
     */
    public function redirect(string $url): StreamedResponse
    {
        $decodedUrl = base64_decode($url);
        $rows = Redirect::query()
            ->where('url', $decodedUrl)
            ->get();

        abort_if($rows->isEmpty(), 404);

        $filename = 'redirect_' . date('d_m_Y') . '.xlsx';

        return $this->streamExcel($filename, function (Spreadsheet $spreadsheet) use ($rows): void {
            $sheet = $spreadsheet->getActiveSheet();

            $sheet
                ->setCellValue('A1', 'Email')
                ->setCellValue('B1', 'Time');

            $rowIndex = 1;

            foreach ($rows as $row) {
                $rowIndex++;

                $sheet
                    ->setCellValue('A' . $rowIndex, $row->email)
                    ->setCellValue('B' . $rowIndex, $row->created_at);
            }
        });
    }

    /**
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
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
     * @param Collection $rows
     * @return array
     */
    private function buildLogStats(int $scheduleId, Collection $rows): array
    {
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

        $total = $rows->count();
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
        if ($ids) {
            return Subscribers::query()
                ->select('subscribers.name', 'subscribers.email')
                ->distinct()
                ->leftJoin('subscriptions', function ($join) use ($ids) {
                    $join->on('subscribers.id', '=', 'subscriptions.subscriber_id')
                        ->whereIn('subscriptions.category_id', $ids);
                })
                ->where('subscribers.active', 1)
                ->whereNotNull('subscriptions.subscriber_id')
                ->get();
        }

        return Subscribers::query()
            ->select('name', 'email')
            ->where('active', 1)
            ->get();
    }
}
