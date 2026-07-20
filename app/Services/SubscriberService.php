<?php

namespace App\Services;

use App\DTO\SubscriberImportData;
use App\Helpers\StringHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use SimpleXMLElement;
use ValueError;
use XMLReader;
use ZipArchive;

class SubscriberService
{
    private const SPREADSHEET_CHUNK_SIZE = 10000;

    private const DATABASE_CHUNK_SIZE = 1000;

    /**
     * Backward-compatible HTTP adapter for spreadsheet imports.
     */
    public function importFromExcel(Request $request, ?callable $onChunkProcessed = null): bool|int
    {
        $import = $this->createImportData($request);

        return $import === null
            ? false
            : $this->importSpreadsheet($import, $onChunkProcessed);
    }

    /**
     * Import a spreadsheet without coupling the import logic to an HTTP request.
     */
    public function importSpreadsheet(
        SubscriberImportData $import,
        ?callable $onChunkProcessed = null,
    ): bool|int {
        if ($import->extension === 'xlsx') {
            return $this->importFromXlsx($import, $onChunkProcessed);
        }

        $reader = $this->createSpreadsheetReader($import->extension);
        $count = 0;

        foreach ($reader->listWorksheetInfo($import->filePath) as $worksheetInfo) {
            $sheetName = $worksheetInfo['worksheetName'];
            $totalRows = (int) $worksheetInfo['totalRows'];

            for ($startRow = 2; $startRow <= $totalRows; $startRow += self::SPREADSHEET_CHUNK_SIZE) {
                $endRow = min($startRow + self::SPREADSHEET_CHUNK_SIZE - 1, $totalRows);
                $chunkReader = $this->createSpreadsheetReader($import->extension);
                $chunkReader->setLoadSheetsOnly([$sheetName]);
                $chunkReader->setReadFilter(new SubscriberImportReadFilter($startRow, $endRow));

                $spreadsheet = $chunkReader->load($import->filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = [];

                for ($row = $startRow; $row <= $endRow; $row++) {
                    $subscriber = $this->normalizeSubscriberRow(
                        $worksheet->getCell('A'.$row)->getValue(),
                        $worksheet->getCell('B'.$row)->getValue(),
                    );

                    if ($subscriber['email'] !== '') {
                        $rows[] = $subscriber;
                    }
                }

                $count = $this->persistChunk(
                    $rows,
                    $import->categoryIds,
                    $count,
                    $onChunkProcessed,
                );

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $rows);
                gc_collect_cycles();
            }
        }

        return $count;
    }

    /**
     * Backward-compatible HTTP adapter for text imports.
     */
    public function importFromText(object $request, ?callable $onChunkProcessed = null): bool|int
    {
        $import = $this->createImportData($request);

        return $import === null
            ? false
            : $this->importTextFile($import, $onChunkProcessed);
    }

    /**
     * Import a text file without coupling the import logic to an HTTP request.
     */
    public function importTextFile(
        SubscriberImportData $import,
        ?callable $onChunkProcessed = null,
    ): bool|int {
        $handle = @fopen($import->filePath, 'rb');

        if ($handle === false) {
            return false;
        }

        $count = 0;
        $rows = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($this->convertToUtf8($line, $import->charset));
                preg_match('/([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/uis', $line, $matches);

                $matchedEmail = $matches[0] ?? '';
                $email = strtolower($matchedEmail);
                $name = trim(str_ireplace($matchedEmail, '', $line));

                if (mb_strlen($name) > 250) {
                    $name = '';
                }

                $rows[] = $this->normalizeSubscriberRow($email, $name);

                if (count($rows) >= self::SPREADSHEET_CHUNK_SIZE) {
                    $count = $this->persistChunk(
                        $rows,
                        $import->categoryIds,
                        $count,
                        $onChunkProcessed,
                    );
                    $rows = [];
                }
            }
        } finally {
            fclose($handle);
        }

        return $this->persistChunk(
            $rows,
            $import->categoryIds,
            $count,
            $onChunkProcessed,
        );
    }

    /**
     * Import XLSX rows by reading worksheet XML directly instead of loading the workbook.
     */
    private function importFromXlsx(
        SubscriberImportData $import,
        ?callable $onChunkProcessed = null,
    ): bool|int {
        $zip = new ZipArchive;

        if ($zip->open($import->filePath) !== true) {
            return false;
        }

        $worksheetPath = $this->getFirstWorksheetPath($zip);
        $sharedStrings = SubscriberSharedStringStore::create($import->filePath);
        $reader = new XMLReader;
        $rows = [];
        $count = 0;

        try {
            if (! $reader->open($this->zipStreamPath($import->filePath, $worksheetPath))) {
                return false;
            }

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                if ((int) $reader->getAttribute('r') <= 1) {
                    continue;
                }

                $row = $this->readXlsxRow($reader->readOuterXml(), $sharedStrings);

                if ($row['email'] === '') {
                    continue;
                }

                $rows[] = $row;

                if (count($rows) >= self::SPREADSHEET_CHUNK_SIZE) {
                    $count = $this->persistChunk(
                        $rows,
                        $import->categoryIds,
                        $count,
                        $onChunkProcessed,
                    );
                    $rows = [];
                }
            }
        } finally {
            $reader->close();
            $zip->close();
            $sharedStrings->close();
        }

        return $this->persistChunk(
            $rows,
            $import->categoryIds,
            $count,
            $onChunkProcessed,
        );
    }

    private function createImportData(object $source): ?SubscriberImportData
    {
        if (! method_exists($source, 'file')) {
            return null;
        }

        $uploadedFile = $source->file('import');

        if ($uploadedFile === null) {
            return null;
        }

        $filePath = $this->resolveUploadedFilePath($uploadedFile);

        if ($filePath === null) {
            return null;
        }

        $extension = method_exists($uploadedFile, 'getClientOriginalExtension')
            ? (string) $uploadedFile->getClientOriginalExtension()
            : (string) pathinfo($filePath, PATHINFO_EXTENSION);

        if (method_exists($source, 'input')) {
            $categoryIds = (array) $source->input('categoryId', []);
            $charset = $source->input('charset');
        } else {
            $categoryIds = (array) ($source->categoryId ?? []);
            $charset = $source->charset ?? null;
        }

        return new SubscriberImportData(
            filePath: $filePath,
            extension: $extension,
            categoryIds: $categoryIds,
            charset: is_string($charset) ? $charset : null,
        );
    }

    private function resolveUploadedFilePath(mixed $uploadedFile): ?string
    {
        if (is_string($uploadedFile)) {
            return $uploadedFile === '' ? null : $uploadedFile;
        }

        if (! is_object($uploadedFile)) {
            return null;
        }

        if (method_exists($uploadedFile, 'getRealPath')) {
            $realPath = $uploadedFile->getRealPath();

            if (is_string($realPath) && $realPath !== '') {
                return $realPath;
            }
        }

        if (method_exists($uploadedFile, 'getPathname')) {
            $pathName = $uploadedFile->getPathname();

            return is_string($pathName) && $pathName !== '' ? $pathName : null;
        }

        return null;
    }

    private function createSpreadsheetReader(string $extension): IReader
    {
        $inputFileType = match ($extension) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            'ods' => 'Ods',
            default => throw new InvalidArgumentException('Unsupported spreadsheet extension.'),
        };

        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        return $reader;
    }

    /**
     * Persist one chunk and report its cumulative progress.
     *
     * @param  array<int, array{email: string, name: string}>  $rows
     * @param  list<int>  $categoryIds
     */
    private function persistChunk(
        array $rows,
        array $categoryIds,
        int $count,
        ?callable $callback,
    ): int {
        $count += $this->importSubscriberRows($rows, $categoryIds);

        if ($callback !== null) {
            $callback($count);
        }

        return $count;
    }

    private function convertToUtf8(string $line, ?string $sourceCharset): string
    {
        if ($sourceCharset === null || strcasecmp($sourceCharset, 'UTF-8') === 0) {
            return $line;
        }

        try {
            $converted = @iconv($sourceCharset, 'UTF-8//IGNORE', $line);
        } catch (ValueError) {
            return $line;
        }

        return $converted === false ? $line : $converted;
    }

    private function getFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);

        if ($workbook === false || $relationships === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $sheet = $workbook->sheets->sheet[0] ?? null;

        if ($sheet === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $attributes = $sheet->attributes(
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
        );
        $relationshipId = (string) ($attributes['id'] ?? '');

        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function zipStreamPath(string $file, string $entry): string
    {
        return 'zip://'.$file.'#'.$entry;
    }

    /**
     * @return array{email: string, name: string}
     */
    private function readXlsxRow(string $rowXml, SubscriberSharedStringStore $sharedStrings): array
    {
        $email = '';
        $name = '';
        $row = simplexml_load_string($rowXml);

        if ($row === false) {
            return $this->normalizeSubscriberRow('', '');
        }

        foreach ($row->c as $cell) {
            $column = preg_replace('/\d+/', '', (string) $cell['r']);

            if ($column !== 'A' && $column !== 'B') {
                continue;
            }

            $value = $this->readXlsxCellValue($cell, $sharedStrings);

            if ($column === 'A') {
                $email = $value;
            } else {
                $name = $value;
            }
        }

        return $this->normalizeSubscriberRow($email, $name);
    }

    private function readXlsxCellValue(
        SimpleXMLElement $cell,
        SubscriberSharedStringStore $sharedStrings,
    ): string {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings->get((int) $cell->v);
        }

        if ($type === 'inlineStr') {
            $value = '';

            foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                $value .= (string) $textNode;
            }

            return $value;
        }

        return (string) ($cell->v ?? '');
    }

    /**
     * @return array{email: string, name: string}
     */
    private function normalizeSubscriberRow(mixed $email, mixed $name): array
    {
        return [
            'email' => strtolower(trim((string) $email)),
            'name' => trim((string) $name),
        ];
    }

    /**
     * Create or update imported subscribers using bulk database operations.
     *
     * @param  array<int, array{email?: mixed, name?: mixed}>  $rows
     * @param  list<int>  $categoryIds
     */
    private function importSubscriberRows(array $rows, array $categoryIds): int
    {
        if ($rows === []) {
            return 0;
        }

        $normalizedRows = [];

        foreach ($rows as $row) {
            $subscriber = $this->normalizeSubscriberRow(
                $row['email'] ?? '',
                $row['name'] ?? '',
            );

            if (! StringHelper::isEmail($subscriber['email']) || mb_strlen($subscriber['email']) > 255) {
                continue;
            }

            $normalizedRows[$subscriber['email']] = [
                'email' => $subscriber['email'],
                'name' => mb_substr($subscriber['name'], 0, 100),
            ];
        }

        if ($normalizedRows === []) {
            return 0;
        }

        $emails = array_keys($normalizedRows);
        $existingSubscriberIds = DB::table('subscribers')
            ->whereIn('email', $emails)
            ->pluck('id', 'email')
            ->all();
        $newSubscribers = [];
        $now = date('Y-m-d H:i:s');

        foreach ($normalizedRows as $email => $row) {
            if (isset($existingSubscriberIds[$email])) {
                continue;
            }

            $newSubscribers[] = [
                'name' => $row['name'],
                'email' => $email,
                'active' => 1,
                'token' => StringHelper::token(),
                'timeSent' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($newSubscribers, self::DATABASE_CHUNK_SIZE) as $chunk) {
            DB::table('subscribers')->insertOrIgnore($chunk);
        }

        $subscriberIds = DB::table('subscribers')
            ->whereIn('email', $emails)
            ->pluck('id')
            ->all();

        $this->syncSubscriptions($subscriberIds, $categoryIds);

        return count($normalizedRows);
    }

    /**
     * @param  array<int, int|string>  $subscriberIds
     * @param  list<int>  $categoryIds
     */
    private function syncSubscriptions(array $subscriberIds, array $categoryIds): void
    {
        $subscriberIds = array_values(array_unique(array_filter($subscriberIds)));

        if ($subscriberIds === []) {
            return;
        }

        DB::table('subscriptions')
            ->whereIn('subscriber_id', $subscriberIds)
            ->delete();

        if ($categoryIds === []) {
            return;
        }

        $rows = [];

        foreach ($subscriberIds as $subscriberId) {
            foreach ($categoryIds as $categoryId) {
                $rows[] = [
                    'subscriber_id' => $subscriberId,
                    'category_id' => $categoryId,
                ];
            }
        }

        foreach (array_chunk($rows, self::DATABASE_CHUNK_SIZE) as $chunk) {
            DB::table('subscriptions')->insertOrIgnore($chunk);
        }
    }
}
