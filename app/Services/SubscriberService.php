<?php

namespace App\Services;

use App\DTO\SubscriberCreateData;
use App\Helpers\StringHelper;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriberService
{
    /**
     * @param Request $request
     * @return bool|int
     */
    public function importFromExcel(Request $request): bool|int
    {
        $iterator = function ($data): \Generator {
            yield from new \ArrayIterator($data);
        };

        $processed_data = function (Worksheet $data) use ($iterator): ?array {
            $key_lists = [];
            $init_data = [];
            foreach ($iterator($data->toArray()) as $item) {
                if (empty($key_lists)) {
                    if (is_null($item[0])) break;

                    $item = array_map(fn($_item) => trim((string)$_item), $item);
                    $key_lists = $item;
                } else {
                    if (empty($key_lists)) break;

                    $item = array_map(fn($_item) => trim((string)$_item), $item);
                    $init_data[] = array_combine($key_lists, $item);
                }
            }

            return (empty($init_data))
                ? null
                : $init_data;
        };

        $extension = strtolower($request->file('import')->getClientOriginalExtension());
        $open_file = function (string $file) use ($extension): ?Spreadsheet {

            switch ($extension) {
                case 'xlsx':
                    $inputFileType = 'Xlsx';
                    break;
                case 'xls':
                    $inputFileType = 'Xls';
                    break;
                case 'csv':
                    $inputFileType = 'Csv';
                    break;
                case 'ods':
                    $inputFileType = 'Ods';
                    break;
            }

            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(false);

            return $reader->load($file);
        };

        $processed = function (Spreadsheet $spreadsheet) use ($iterator, $processed_data, $request): int {
            $count = 0;
            $sheetNames = [];
            foreach ($iterator($spreadsheet->getSheetNames()) ?? [] as $item_name) {
                $sheetNames[] = $item_name;
            }

            $sheetCount = $spreadsheet->getSheetCount();

            $i = 0;
            while ($i < $sheetCount) {
                $options = $processed_data($spreadsheet->getSheet($i));

                foreach ($options ?? [] as $option) {
                    $keys = array_keys($option);
                    $email = $option[$keys[0]];
                    $name = $option[$keys[1]];

                    if (StringHelper::isEmail($email)) {
                        $subscriber = Subscribers::where('email', 'like', $email)->first();

                        if ($subscriber) {
                            $subscriber->remove();
                            foreach ($request->categoryId ?? [] as $categoryId) {
                                if (is_numeric($categoryId)) {
                                    Subscriptions::create([
                                        'subscriber_id' => $subscriber->id,
                                        'category_id' => $categoryId,
                                    ]);
                                }
                            }
                        } else {

                            $insertId = Subscribers::create(new SubscriberCreateData(
                                email: $email,
                                active: 1,
                                token: StringHelper::token(),
                                timeSent: date('Y-m-d H:i:s'),
                                name: $name,
                            ))->id;

                            foreach ($request->categoryId ?? [] as $category) {
                                if (is_numeric($category)) {
                                    Subscriptions::create([
                                        'subscriber_id' => $insertId,
                                        'category_id' => $category,
                                    ]);
                                }
                            }

                            $count++;
                        }
                    }
                }

                $i++;
            }

            return $count;
        };

        $worksheetData = $open_file($request->file('import'));

        return $processed($worksheetData);
    }

    /**
     * @param object $f
     * @return bool|int
     */
    public function importFromText(object $f): bool|int
    {
        if (!($fp = @fopen($f->file('import'), "rb"))) {
            return false;
        } else {
            $buffer = fread($fp, filesize($f->file('import')));

            fclose($fp);

            $strTmp = explode("\n", $buffer);
            $count = 0;

            foreach ($strTmp ?? [] as $val) {
                $str = trim($val);

                if ($f->charset) {
                    $str = iconv($str, 'utf-8', $f->charset);
                }

                preg_match('/([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/uis', $str, $out);

                $email = $out[0] ?? '';
                $name = str_replace($email, '', $str);
                $email = strtolower($email);
                $name = trim($name);

                if (mb_strlen($name) > 250) {
                    $name = '';
                }

                if ($email) {
                    $subscriber = Subscribers::where('email', 'like', $email)->first();

                    if ($subscriber) {
                        Subscriptions::where('subscriber_id', $subscriber->id)->delete();

                        foreach ($f->categoryId ?? [] as $categoryId) {
                            if (is_numeric($categoryId)) {
                                Subscriptions::create(['subscriber_id' => $subscriber->id, 'category_id' => $categoryId]);
                            }
                        }

                    } else {
                        $insertId = Subscribers::create(
                            new SubscriberCreateData(
                                email: $email,
                                active: 1,
                                token: StringHelper::token(),
                                timeSent: date('Y-m-d H:i:s'),
                                name: $name,
                            )
                        )->id;

                        if ($insertId) $count++;

                        foreach ($f->categoryId ?? [] as $categoryId) {
                            if (is_numeric($categoryId)) {
                                Subscriptions::create(['subscriber_id' => $insertId, 'category_id' => $categoryId]);
                            }
                        }
                    }
                }
            }
        }

        return $count;
    }
}
