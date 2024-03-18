<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadySent extends Model
{
    protected $table = 'ready_sent';

    protected $primaryKey = 'id';

    protected $fillable = [
        'subscriberId',
        'email',
        'templateId',
        'template',
        'success',
        'errorMsg',
        'readMail',
        'date',
        'scheduleId',
        'logId'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(Schedule::class, 'scheduleId','id');
    }
}
