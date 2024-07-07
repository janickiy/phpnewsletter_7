<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Macros extends Model
{
    public const TYPE_URL = 1;
    public const TYPE_EMAIL = 2;
    public const TYPE_HASH_TAGS = 3;
    public const TYPE_TAGS = 4;
    public const TYPE_WRAP_PHRASE = 5;

    protected $table = 'macros';

    protected $fillable = [
        'name',
        'value',
        'type'
    ];

    /**
     * @return array
     */
    public static function getOption(): array
    {
        return [
            self::TYPE_URL   => trans('frontend.str.macros_type_url'),
            self::TYPE_EMAIL => trans('frontend.str.macros_type_email'),
            self::TYPE_HASH_TAGS   => trans('frontend.str.macros_type_hash_tags'),
            self::TYPE_TAGS        => trans('frontend.str.macros_type_tags'),
            self::TYPE_WRAP_PHRASE => trans('frontend.str.macros_type_wrap_phrase'),
        ];
    }
}
