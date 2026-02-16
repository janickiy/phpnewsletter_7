<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\StringHelper;

class Templates extends Model
{
    use StaticTableName;

    protected $table = 'templates';

    protected $fillable = [
        'name',
        'body',
        'prior'
    ];

    /**
     * @return HasMany
     */
    public function attach(): hasMany
    {
        return $this->hasMany(Attach::class, 'template_id');
    }

    /**
     * @return string
     */
    public function excerpt(): string
    {
        $content = $this->body;
        $content = preg_replace('/(<.*?>)|(&.*?;)/', '', $content);

        return StringHelper::shortText($content, 500);
    }

    /**
     * @return array
     */
    public static function getOption(): array
    {
        return self::orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    /**
     * @return string
     */
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

    /**
     * @return void
     */
    public function scopeRemove(): void
    {
        foreach ($this->attach ?? [] as $attach) {
            $attach->remove();
        }

        $this->delete();
    }
}
