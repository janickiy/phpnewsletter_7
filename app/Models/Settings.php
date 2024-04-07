<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\StringHelper;

class Settings extends Model
{
    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * @param string $name
     * @return void
     */
    public function setNameAttribute(string $name): void
    {
        $this->attributes['name'] = str_replace(' ', '_', strtoupper($name));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function setValue(string $key, mixed $value): void
    {
        $setting = self::where('name', $key)->first();

        if ($value === null) $value = '';

        if ($key == 'URL' && trim($value) == '')  $value = StringHelper::getUrl();

        if ($setting) {
            $setting->value = $value;
            $setting->save();
        }
    }
}
