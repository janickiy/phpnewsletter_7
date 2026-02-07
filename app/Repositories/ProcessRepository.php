<?php

namespace App\Repositories;


use App\Enums\ProcessStatus;
use App\Models\Process;

class ProcessRepository extends BaseRepository
{
    public function __construct(Process $model)
    {
        parent::__construct($model);
    }

    /**
     * @param int $user_id
     * @param string $command
     * @return bool
     */
    public function updateByUserId(int $user_id, string $command): bool
    {
        $model = $this->model->where('user_id', $user_id);

        if ($model->first()) {
            return $model->update(['command' => $command]);
        } else {
            $this->model->command = $command;
            $this->model->user_id = $user_id;
            return $this->model->save();
        }
    }

    /**
     * @param int $user_id
     * @return string
     */
    public function getProcess(int $user_id): string
    {
        $model = $this->model->where('user_id', $user_id)->first();

        if ($model) {
            return $model->command;
        } else {
            $this->model->command = ProcessStatus::Start->value;
            $this->model->user_id = $user_id;
            $this->model->save();

            return 'start';
        }
    }
}
