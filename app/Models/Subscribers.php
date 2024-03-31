<?php

namespace App\Models;

use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

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
        return $this->hasMany(Subscriptions::class,'subscriber_id');
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
            $tok = strtok($buffer, "\n");

            while ($tok) {
                $tok = strtok("\n");
                $strtmp[] = $tok;
            }

            $count = 0;

            foreach ($strtmp as $val) {
                $str = $val;

                if ($f->charset) {
                    $str = iconv($str, 'utf-8', $f->charset);
                }

                preg_match('/([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/uis', $str, $out);

                $email = isset($out[0]) ? $out[0] : '';
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

                        if ($f->categoryId) {
                            foreach ($f->categoryId as $categoryId) {
                                if (is_numeric($categoryId)) {
                                    Subscriptions::create(['subscriber_id' => $subscriber->id, 'category_id' => $categoryId]);
                                }
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

                        if ($f->categoryId) {
                            foreach ($f->categoryId as $categoryId) {
                                if (is_numeric($categoryId)) {
                                    Subscriptions::create(['subscriber_id' => $insertId, 'category_id' => $categoryId]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $count;
    }

    /**
     * @param object $f
     * @return false|int
     */
    public static function importFromExcel(object $f): false|int
    {
        $ext = strtolower($f->file('import')->getClientOriginalExtension());

        if ($ext == 'csv') {
            $reader = new Csv();

            if ($ext == 'csv' && $f->charset) {
                $reader->setInputEncoding($f->charset);
            }

        } elseif ($ext == 'xlsx') {
            $reader = new Xlsx();
        } else {
            $reader = new Xls();
        }

        $count = 0;

        $spreadsheet = $reader->load($f->file('import'));

        if (!$spreadsheet) return false;

        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($allDataInSheet as $dataInSheet) {
            $email = trim($dataInSheet['A']);
            $name = trim($dataInSheet['B']);

            if (StringHelper::isEmail($email)) {
                $subscribers = Subscribers::where('email', 'like', $email)->first();

                if ($subscribers && $f->categoryId) {
                    Subscriptions::where('subscriber_id', $subscribers->id)->delete();

                    foreach ($f->categoryId as $categoryId) {
                        if (is_numeric($categoryId)) {
                            Subscriptions::create([
                                'subscriber_id' => $subscribers->id,
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

                    if ($f->categoryId) {
                        foreach ($f->categoryId as $category) {
                            if (is_numeric($category)) {
                                Subscriptions::create([
                                    'subscriber_id' => $insertId,
                                    'category_id' => $category,
                                ]);
                            }
                        }
                    }

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * @param array $categoryId
     * @return mixed
     */
    public static function getSubscribersList(array $categoryId)
    {
        if ($categoryId) {
            $temp = [];
            foreach ($categoryId as $id) {
                if (is_numeric($id)) {
                    $temp[] = $id;
                }
            }

            $subscribers = Subscribers::select('subscribers.name', 'subscribers.email')
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
            $subscribers = Subscribers::select('name', 'email')
                ->where('active', 1)
                ->get();
        }

        return $subscribers;
    }
}
