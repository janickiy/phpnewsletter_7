<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{

    protected $table = 'settings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * @param $value
     */
    public function setNameAttribute($name) {
        $this->attributes['name'] = str_replace(' ', '_', strtoupper($name));
    }
}
