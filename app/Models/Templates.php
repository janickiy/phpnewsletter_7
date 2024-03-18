<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\StringHelper;

class Templates  extends Model
{
    protected $table = 'templates';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'body',
        'prior'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attach()
    {
        return $this->hasMany(Attach::class, 'templateId', 'id');
    }

    /**
     * @return string
     */
    public function excerpt()
    {
        $content = $this->body;
        $content = preg_replace('/(<.*?>)|(&.*?;)/', '', $content);

        return StringHelper::shortText($content,500);
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
}
