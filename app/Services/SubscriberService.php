<?php

namespace App\Services;


use App\DTO\SubscriberCreateData;
use App\Helpers\StringHelper;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class SubscriberService
{
    private const SPREADSHEET_CHUNK_SIZE = 1000;

    /**
     * @param Request $request
     * @return bool|int
     */
    public function importFromExcel(Request $request): bool|int
    {
        $extension = strtolower($request->file('import')->getClientOriginalExtension());
        $file = $request->file('import')->getRealPath();

        if ($file === false) {
            return false;
        }

        $reader = $this->createSpreadsheetReader($extension);
        $count = 0;

        foreach ($reader->listWorksheetInfo($file) as $worksheetInfo) {
            $sheetName = $worksheetInfo['worksheetName'];
            $totalRows = (int) $worksheetInfo['totalRows'];

            for ($startRow = 2; $startRow <= $totalRows; $startRow += self::SPREADSHEET_CHUNK_SIZE) {
                $endRow = min($startRow + self::SPREADSHEET_CHUNK_SIZE - 1, $totalRows);

                $chunkReader = $this->createSpreadsheetReader($extension);
                $chunkReader->setLoadSheetsOnly([$sheetName]);
                $chunkReader->setReadFilter(new SubscriberImportReadFilter($startRow, $endRow));

                $spreadsheet = $chunkReader->load($file);
                $worksheet = $spreadsheet->getActiveSheet();

                for ($row = $startRow; $row <= $endRow; $row++) {
                    $email = strtolower(trim((string) $worksheet->getCell('A' . $row)->getValue()));
                    $name = trim((string) $worksheet->getCell('B' . $row)->getValue());

                    if ($email === '') {
                        continue;
                    }

                    $count += $this->importSubscriber($email, $name, (array) ($request->categoryId ?? []));
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet);
                gc_collect_cycles();
            }
        }

        return $count;
    }

    /**
     * @param object $f
     * @return bool|int
     */
    public function importFromText(object $f): bool|int
    {
        if (!($fp = @fopen($f->file('import'), "rb"))) {
            return false;
        }

        $count = 0;

        while (($line = fgets($fp)) !== false) {
            $str = trim($line);

            if ($f->charset) {
                $str = iconv($str, 'utf-8', $f->charset);
            }

            preg_match('/([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/uis', $str, $out);

            $email = strtolower($out[0] ?? '');
            $name = trim(str_replace($email, '', $str));

            if (mb_strlen($name) > 250) {
                $name = '';
            }

            $count += $this->importSubscriber($email, $name, (array) ($f->categoryId ?? []));
        }

        fclose($fp);

        return $count;
    }

    /**
     * Create a configured spreadsheet reader by file extension.
     *
     * @param string $extension
     * @return \PhpOffice\PhpSpreadsheet\Reader\IReader
     */
    private function createSpreadsheetReader(string $extension): \PhpOffice\PhpSpreadsheet\Reader\IReader
    {
        $inputFileType = match ($extension) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            'ods' => 'Ods',
            default => throw new \InvalidArgumentException('Unsupported spreadsheet extension.'),
        };

        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);

        return $reader;
    }

    /**
     * Create or update one subscriber from imported row data.
     *
     * @param string $email
     * @param string $name
     * @param array $categoryIds
     * @return int
     */
    private function importSubscriber(string $email, string $name, array $categoryIds): int
    {
        if (!StringHelper::isEmail($email)) {
            return 0;
        }

        $subscriber = Subscribers::query()
            ->where('email', $email)
            ->first();

        if ($subscriber) {
            Subscriptions::where('subscriber_id', $subscriber->id)->delete();
            $this->syncSubscriptions($subscriber->id, $categoryIds);

            return 0;
        }

        $subscriberId = Subscribers::create((new SubscriberCreateData(
            email: $email,
            active: 1,
            token: StringHelper::token(),
            timeSent: date('Y-m-d H:i:s'),
            name: $name,
        ))->toArray())->id;

        $this->syncSubscriptions($subscriberId, $categoryIds);

        return 1;
    }

    /**
     * @param int $subscriberId
     * @param array $categoryIds
     * @return void
     */
    private function syncSubscriptions(int $subscriberId, array $categoryIds): void
    {
        foreach (array_unique($categoryIds) as $categoryId) {
            if (!is_numeric($categoryId)) {
                continue;
            }

            Subscriptions::query()->insertOrIgnore([
                'subscriber_id' => $subscriberId,
                'category_id' => (int) $categoryId,
            ]);
        }
    }
}

final class SubscriberImportReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
    ) {
    }

    /**
     * Read only the email and name columns for the current chunk.
     *
     * @param string $columnAddress
     * @param int $row
     * @param string $worksheetName
     * @return bool
     */
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return in_array($columnAddress, ['A', 'B'], true)
            && $row >= $this->startRow
            && $row <= $this->endRow;
    }
}
