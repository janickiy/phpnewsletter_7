<?php

namespace App\Repositories;

use App\Models\Smtp;


class SmtpRepository extends BaseRepository
{
    public function __construct(Smtp $model)
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
     * @param int $action
     * @param array $Ids
     * @return void
     */
    public function updateStatus(int $action, array $Ids): void
    {
        $idList = [];

        foreach ($Ids as $id) {
            if (is_numeric($id)) {
                $idList[] = $id;
            }
        }

        switch ($action) {
            case  0 :
            case  1 :
                $this->model->whereIN('id', $idList)->update(['active' => $action]);
                break;

            case 2 :
                $this->model->whereIN('id', $idList)->delete();
                break;
        }
    }

    private function mapping(array $data): array
    {
        return collect($data)
            ->merge([
                'password' => $data['password'] ?? null,
            ])
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if (in_array($key, ['port', 'timeout']) && !is_null($value)) {
                    return (int)$value;
                }
                return $value;
            })
            ->all();
    }

}
