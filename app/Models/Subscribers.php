<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Subscribers extends Model
{
    use HasFactory, Notifiable, StaticTableName;

    protected $table = 'subscribers';

    protected $fillable = [
        'name',
        'email',
        'active',
        'timeSent',
        'token',
    ];

    protected $hidden = [
        'token',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscriptions::class, 'subscriber_id');
    }

    public function remove(): bool
    {
        $this->subscriptions()->delete();

        return (bool) $this->delete();
    }
}
