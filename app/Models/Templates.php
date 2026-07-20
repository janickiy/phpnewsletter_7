<?php

namespace App\Models;

use App\Helpers\StringHelper;
use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Templates extends Model
{
    use StaticTableName;

    protected $table = 'templates';

    protected $fillable = [
        'name',
        'body',
        'prior',
    ];

    public function attach(): HasMany
    {
        return $this->hasMany(Attach::class, 'template_id');
    }

    public function excerpt(): string
    {
        $content = $this->body;
        $content = preg_replace('/(<.*?>)|(&.*?;)/', '', $content);

        return StringHelper::shortText($content, 500);
    }

    public static function getOption(): array
    {
        return self::orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    public function getPrior(): string
    {
        switch ($this->prior) {
            case 1:
                return __('frontend.str.high');
            case 2:
                return __('frontend.str.low');
            default:
                return __('frontend.str.normal');
        }
    }

    public function remove(): bool
    {
        foreach ($this->attach ?? [] as $attach) {
            $attach->remove();
        }

        return (bool) $this->delete();
    }
}
