<?php

namespace App\Repositories;

use App\Models\Templates;

class TemplateRepository extends BaseRepository
{
    public function __construct(Templates $model)
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
     * @return array
     */
    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    /**
     * @param int $id
     * @return void
     */
    public function remove(int $id)
    {
        $this->model->remove($id);
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if ($key === 'prior' && !is_null($value)) {
                    return (int)$value;
                }
                return $value;
            })
            ->all();
    }
}
