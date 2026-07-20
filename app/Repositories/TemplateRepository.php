<?php

namespace App\Repositories;

use App\Models\Templates;

class TemplateRepository extends BaseRepository
{
    public function __construct(Templates $model)
    {
        parent::__construct($model);
    }

    public function updateWithMapping(int $id, array $data): bool
    {
        return $this->update($id, $this->mapping($data));
    }

    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    public function updateStatus(array $Ids, int $action): void
    {
        if ($action === 1) {
            $templates = $this->model->whereIN('id', $Ids)->get();

            foreach ($templates as $template) {
                $template->remove();
            }
        }
    }

    public function remove(int $id): bool
    {
        $template = $this->model->find($id);

        return $template?->remove() ?? false;
    }

    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if ($key === 'prior' && ! is_null($value)) {
                    return (int) $value;
                }

                return $value;
            })
            ->all();
    }
}
