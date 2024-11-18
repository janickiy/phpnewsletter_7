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
     * @return array
     */
    public static function getOption(): array
    {
        return self::orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    public function scopeRemove(): void
    {
        Subscriptions::where('category_id', $this->id)->delete();
        ScheduleCategory::where('category_id', $this->id)->delete();
        self::delete();
    }
}
