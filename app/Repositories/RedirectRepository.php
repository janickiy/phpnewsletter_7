<?php

namespace App\Repositories;

use App\DTO\RedirectCreateData;
use App\Models\Redirect;

class RedirectRepository extends BaseRepository
{
    public function __construct(Redirect $model)
    {
        parent::__construct($model);
    }

    /**
     * @param RedirectCreateData $data
     * @return Redirect
     */
    public function add(RedirectCreateData $data): Redirect
    {
        return $this->model->query()->create($data->toArray());
    }
}
