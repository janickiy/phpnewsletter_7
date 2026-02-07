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
     * @param array $data
     * @return Attach|null
     */
    public function update(int $id, array $data): ?Attach
    {
        $model = $this->model->find($id);

        if ($model) {
            $model->name = $data['name'];

            $model->save();

            return $model;
        }
        return null;
    }

    /**
     * @param int $id
     * @return bool|mixed
     */
    public function remove(int $id): ?bool
    {
        $model = $this->model->find($id);

        if ($model) {
            $model->remove();
        }
    }

}
