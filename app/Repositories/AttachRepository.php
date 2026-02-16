<?php

namespace App\Repositories;

use App\Models\Attach;


class AttachRepository extends BaseRepository
{
    public function __construct(Attach $model)
    {
        parent::__construct($model);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {
        $model = $this->model->find($id);

        if (!$model) {
            return false;
        }

        $model->remove();

        return true;
    }
}
