<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscribers extends Model
{
    protected $table = 'subscribers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'email',
        'active',
        'timeSent',
        'token'
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('active', 'true');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscriptions::class,'subscriberId','id');
    }
}
