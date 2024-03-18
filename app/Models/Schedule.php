<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Schedule extends Model
{
    protected $table = 'schedule';

    protected $primaryKey = 'id';

    protected $fillable = [
        'value_from_start_date',
        'value_from_end_date',
        'templateId'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(Templates::class, 'templateId','id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function categories()
    {
        return $this->hasManyThrough(Category::class, ScheduleCategory::class,'scheduleId','id','id','categoryId');
    }

}
