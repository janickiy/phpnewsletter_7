<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
       return $this->update($id, ['name' => $data['name']]);
    }

    /**
     * @return array
     */
    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
    }
}
