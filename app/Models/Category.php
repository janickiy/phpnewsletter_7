<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name'
    ];

    /**
     * @return mixed
     */
    public static function getOption()
    {
        return Category::orderBy('name')->get()->pluck('name', 'id');
    }
}
