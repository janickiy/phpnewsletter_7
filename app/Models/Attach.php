<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Storage;

class Attach extends Model
{
    public const DIRECTORY = 'public/attach';

    protected $table = 'attach';

    protected $fillable = [
        'name',
        'file_name',
        'template_id'
    ];

    protected $attributes = [
        'name' => 'user',
    ];

    /**
     * @return HasOne
     */
    public function template(): HasOne
    {
        return $this->hasOne(Templates::class);
    }

    /**
     * @param Builder $query
     * @param int $id
     * @return false|mixed
     */
    public function scopeRemove(Builder $query, int $id)
    {
        $q = $query->where('id', $id);

        if ($q->exists()) {
            $attach = $q->first();

            if (Storage::exists(Attach::DIRECTORY . '/' . $attach->file_name)) {
                Storage::delete(Attach::DIRECTORY . '/' . $attach->file_name);
            }

            return $q->delete();
        }

        return false;
    }
}
