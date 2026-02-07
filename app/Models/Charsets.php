<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\StringHelper;

class Charsets extends Model
{
    use StaticTableName;

    protected $table = 'charsets';

    public $timestamps = false;

    /**
     * @return array
     */
    public static function getOption(): array
    {
        $charsets = [];

        foreach (self::get() ?? [] as $row) {
            $charsets[$row->charset] = StringHelper::charsetList($row->charset);
        }

        return $charsets;
    }
}
