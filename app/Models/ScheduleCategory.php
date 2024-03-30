<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleCategory extends Model
{
    protected $table = 'schedule_category';

    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'category_id'
    ];

    protected $primaryKey = ['schedule_id', 'category_id'];

    public $incrementing = false;
}
