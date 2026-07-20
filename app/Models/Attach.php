<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attach extends Model
{
    use StaticTableName;

    public const DIRECTORY = 'public/attach';

    protected $table = 'attach';

    protected $fillable = [
        'name',
        'file_name',
        'template_id',
    ];

    protected $attributes = [
        'name' => 'user',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Templates::class);
    }

    public function remove(): bool
    {
        $path = self::DIRECTORY.'/'.$this->file_name;

        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        return (bool) $this->delete();
    }
}
