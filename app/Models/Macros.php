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
}
