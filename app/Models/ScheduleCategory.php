<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleCategory extends Model
{
    protected $table = 'schedule_category';

    public $timestamps = false;

    protected $fillable = [
        'scheduleId',
        'categoryId'
    ];
}
