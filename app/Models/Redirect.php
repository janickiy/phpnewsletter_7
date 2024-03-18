<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $table = 'redirect';

    protected $primaryKey = 'id';

    protected $fillable = [
        'url',
        'email'
    ];
}
