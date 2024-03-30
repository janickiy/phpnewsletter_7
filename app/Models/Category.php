<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Templates::class, 'subscriberId');
    }
}
