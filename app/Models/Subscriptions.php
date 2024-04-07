<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscriptions extends Model
{
    protected $table = 'subscriptions';

    public $timestamps = false;

    protected $primaryKey = ['subscriber_id', 'category_id'];

    public $incrementing = false;

    protected $fillable = [
        'subscriber_id',
        'category_id'
    ];

    /**
     * @return HasOne
     */
    public function subscriber(): HasOne
    {
        return $this->hasOne(Subscribers::class,'id','subscriber_id');
    }

    /**
     * @return HasOne
     */
    public function category(): HasOne
    {
        return $this->hasOne(Category::class,'id','category_id');
    }
}
