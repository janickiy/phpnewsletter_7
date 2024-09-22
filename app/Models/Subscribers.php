<?php

namespace App\Models;

use App\Helpers\StringHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscribers extends Model
{
    protected $table = 'subscribers';

    protected $fillable = [
        'name',
        'email',
        'active',
        'timeSent',
        'token'
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('active', 'true');
    }

    /**
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscriptions::class, 'subscriber_id');
    }

    /**
     * @param object $f
     * @return bool|int
     */
    public static function importFromText(object $f): bool|int
    {
        if (!($fp = @fopen($f->file('import'), "rb"))) {
            return false;
        } else {
            $buffer = fread($fp, filesize($f->file('import')));

            fclose($fp);

            $strtmp = explode("\n", $buffer);
            $count = 0;

            foreach ($strtmp ?? [] as $val) {
                $str = trim($val);

                if ($f->charset) {
                    $str = iconv($str, 'utf-8', $f->charset);
                }

                preg_match('/([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/uis', $str, $out);

                $email = $out[0] ?? '';
                $name = str_replace($email, '', $str);
                $email = strtolower($email);
                $name = trim($name);

                if (strlen($name) > 250) {
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
                        $data['name'] = $name;
                        $data['email'] = $email;
                        $data['token'] = StringHelper::token();
                        $data['timeSent'] = date('Y-m-d H:i:s');
                        $data['active'] = 1;

                        $insertId = Subscribers::create($data)->id;

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

    /**
     * @param Request $request
     * @return int
     */
    public static function importFromExcel(Request $request): int
    {
        $iterator = static function ($data): \Generator {
            yield from new \ArrayIterator($data);
        };

        $processed_data = static function (Worksheet $data) use ($iterator): ?array {
            $key_lists = [];
            $init_data = [];
            foreach ($iterator($data->toArray()) as $item) {
                if (empty($key_lists)) {
                    if (is_null($item[0])) break;

                    $item = array_map(static fn($_item) => trim((string)$_item), $item);
                    $key_lists = $item;
                } else {
                    if (empty($key_lists)) break;

                    $item = array_map(static fn($_item) => trim((string)$_item), $item);
                    $init_data[] = array_combine($key_lists, $item);
                }
            }

            return (empty($init_data))
                ? null
                : $init_data;
        };

        $extension = strtolower($request->file('import')->getClientOriginalExtension());
        $open_file = static function (string $file) use ($extension): ?Spreadsheet {

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

        $processed = static function (Spreadsheet $spreadsheet) use ($iterator, $processed_data, $request): int {
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

                            $insertId = Subscribers::create([
                                'name' => $name,
                                'email' => $email,
                                'active' => 1,
                                'timeSent' => date('Y-m-d H:i:s'),
                                'token' => StringHelper::token()
                            ])->id;

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
     * @param array $categoryId
     * @return mixed
     */
    public static function getSubscribersList(array $categoryId)
    {
        if ($categoryId) {
            $temp = [];
            foreach ($categoryId ?? [] as $id) {
                if (is_numeric($id)) {
                    $temp[] = $id;
                }
            }

            $subscribers = self::select('subscribers.name', 'subscribers.email')
                ->leftJoin('subscriptions', function ($join) {
                    $join->on('subscribers.id', '=', 'subscriptions.subscriber_id');
                })
                ->where('subscribers.active', 1)
                ->whereIn('subscriptions.category_id', $temp)
                ->groupBy('subscribers.email')
                ->groupBy('subscribers.id')
                ->groupBy('subscribers.name')
                ->get();
        } else {
            $subscribers = self::select('name', 'email')
                ->active()
                ->get();
        }

        return $subscribers;
    }

    /**
     * @return void
     */
    public function scopeRemove(): void
    {
        foreach ($this->subscriptions ?? [] as $subscription) {
            $subscription->delete();
        }

        $this->delete();
    }
}
