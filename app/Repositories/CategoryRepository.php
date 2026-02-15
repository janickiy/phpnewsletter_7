<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Company;


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

    public function updateWithMapping(int $id, array $data): bool
    {

    }

    /**
     * @return array
     */
    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    private function mapping(array $data): array
    {
        return collect($data)
            ->merge([
                'meta_title' => $data['meta_title'] ?? null,
            ])
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if ($key === 'customer_id' && !is_null($value)) {
                    return (int)$value;
                }
                return $value;
            })
            ->all();
    }
}
