<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadySent extends Model
{
    protected $table = 'ready_sent';

    protected $fillable = [
        'subscriber_id',
        'email',
        'template_id',
        'template',
        'success',
        'errorMsg',
        'readMail',
        'date',
        'schedule_id',
        'log_id',
    ];

    /**
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscribers::class, 'subscriber_id');
    }

    /**
     * @return BelongsTo
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Logs::class, 'log_id');
    }
}
