<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Schedule extends Model
{
    protected $table = 'schedule';

    protected $fillable = [
        'start_date',
        'end_date',
        'template_id'
    ];

    /**
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Templates::class, 'template_id');
    }

    /**
     * @return HasManyThrough
     */
    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(Category::class, ScheduleCategory::class,'schedule_id','id','id','category_id');
    }

}
