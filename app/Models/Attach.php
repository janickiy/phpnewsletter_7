<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Storage;

class Attach extends Model
{
    const DIRECTORY = 'public/attach';

    protected $table = 'attach';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'file_name',
        'templateId'
    ];

    protected $attributes = [
        'name' => 'user',
    ];

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
