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
     * @return Category|null
     */
    public function update(int $id, array $data): ?Category
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
     * @return array
     */
    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
    }


}
