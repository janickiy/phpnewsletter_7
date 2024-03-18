<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smtp  extends Model
{
    protected $table = 'smtp';

    protected $primaryKey = 'id';

    protected $fillable = [
        'host',
        'email',
        'username',
        'password',
        'port',
        'authentication',
        'secure',
        'timeout',
        'active'
    ];
}
