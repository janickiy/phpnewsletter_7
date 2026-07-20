<?php

namespace Database\Seeders;

use App\Models\Charsets;
use Illuminate\Database\Seeder;

class CharsetSeeder extends Seeder
{
    private const CHARSETS = [
        'utf-8',
        'iso-8859-1',
        'iso-8859-2',
        'iso-8859-3',
        'iso-8859-4',
        'iso-8859-5',
        'iso-8859-6',
        'iso-8859-7',
        'iso-8859-8',
        'iso-8859-9',
        'iso-8859-10',
        'iso-8859-13',
        'iso-8859-14',
        'iso-8859-15',
        'iso-8859-16',
        'windows-1250',
        'windows-1251',
        'windows-1252',
        'windows-1253',
        'windows-1254',
        'windows-1255',
        'windows-1256',
        'windows-1257',
        'windows-1258',
        'gb2312',
        'big5',
        'iso-2022-jp',
        'ks_c_5601-1987',
        'euc-kr',
        'windows-874',
        'koi8-r',
        'koi8-u',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CHARSETS as $charset) {
            Charsets::query()->firstOrCreate(['charset' => $charset]);
        }
    }
}
