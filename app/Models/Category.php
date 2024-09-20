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

    /**
     * @return mixed
     */
    public static function getOption(): mixed
    {
        return self::orderBy('name')->get()->pluck('name', 'id');
    }

    public function scopeRemove(): void
    {
        Subscriptions::where('category_id', $this->id)->delete();
        ScheduleCategory::where('category_id', $this->id)->delete();
        self::delete();
    }
}
