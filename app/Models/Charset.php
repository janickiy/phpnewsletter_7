<?php

namespace App\Models;

use App\Helpers\StringHelpers;
use Illuminate\Database\Eloquent\Model;

class Charset extends Model
{
    protected $table = 'charset';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @return array
     */
    public static function getOption()
    {
        $charsets = [];

        foreach (Charset::get() as $row) {
            $charsets[$row->charset] = StringHelpers::charsetList($row->charset);
        }

        return $charsets;
    }

}
