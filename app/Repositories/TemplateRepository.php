<?php

namespace App\Repositories;

use App\Models\Templates;


class TemplateRepository extends BaseRepository
{
    public function __construct(Templates $model)
    {
        parent::__construct($model);
    }


}
