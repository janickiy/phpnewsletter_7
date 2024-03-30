<?php

namespace App\Models;

class Subscriptions
{
    protected $table = 'subscriptions';

    public $timestamps = false;

    protected $primaryKey = ['subscriber_id', 'category_id'];

    public $incrementing = false;

    protected $fillable = [
        'subscriber_id',
        'category_id'
    ];
}
