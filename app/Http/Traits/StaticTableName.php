<?php

namespace App\Http\Traits;

trait StaticTableName
{
    public static function getTableName()
    {
        return with(new static)->getTable();
    }
}
