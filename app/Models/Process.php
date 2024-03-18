<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Process extends Model
{
    protected $table = 'process';

    protected $primaryKey = 'id';

    protected $fillable = [
        'command',
        'userId'
    ];
}
