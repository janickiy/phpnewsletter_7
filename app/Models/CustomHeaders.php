<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomHeaders extends Model
{
    protected $table = 'customheaders';

    protected $fillable = [
        'name',
        'value'
    ];

}
