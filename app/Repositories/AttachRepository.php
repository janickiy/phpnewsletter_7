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
     * @return bool
     */
    public function updateWithMapping(int $id, array $data): bool
    {
        return $this->update($id, $this->mapping($data));
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

    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->all();
    }

}
