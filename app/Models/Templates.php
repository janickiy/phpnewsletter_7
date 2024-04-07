<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\StringHelper;

class Templates extends Model
{
    protected $table = 'templates';

    protected $fillable = [
        'name',
        'body',
        'prior'
    ];

    /**
     * @return HasMany
     */
    public function attach()
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
     * @return mixed
     */
    public static function getOption()
    {
        return self::orderBy('name')->get()->pluck('name', 'id');
    }

    /**
     * @param int $prior
     * @return string
     */
    public static function getPrior(int $prior): string
    {
        switch ($prior) {
            case 1:
                return trans('frontend.str.high');
            case 2:
                return trans('frontend.str.low');
            case 3:
                return trans('frontend.str.normal');
        }
    }

    /**
     * @return void
     */
    public function scopeRemove(): void
    {
        foreach ($this->attach() as $attach) {
            $attach->remove();
        }

        $this->delete();
    }
}
