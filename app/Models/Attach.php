<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Templates::class);
    }

    /**
     * @return void
     */
    public function scopeRemove(): void
    {
        if (Storage::exists(Attach::DIRECTORY . '/' . $this->file_name)) {
            Storage::delete(Attach::DIRECTORY . '/' . $this->file_name);
        }

        $this->delete();

    }
}
