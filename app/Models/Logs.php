<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    protected $table = 'logs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'time',
    ];
}
