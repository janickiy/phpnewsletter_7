<?php

namespace App\Models;

use App\Helpers\StringHelper;
use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Charsets extends Model
{
    use StaticTableName;

    protected $table = 'charsets';

    public $timestamps = false;

    protected $fillable = [
        'charset',
    ];

    public static function getOption(): array
    {
        return Charsets::orderBy('charset')
            ->get()
            ->pluck('charset')
            ->mapWithKeys(fn (string $charset) => [
                $charset => StringHelper::charsetList($charset),
            ])
            ->toArray();
    }
}
