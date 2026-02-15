<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use StaticTableName;

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

    public function scopeRemove(): void
    {
        Subscriptions::where('category_id', $this->id)->delete();
        ScheduleCategory::where('category_id', $this->id)->delete();
        self::delete();
    }
}
