<?php

namespace App\Repositories;

use App\Models\ScheduleCategory;


class ScheduleCategoryRepository extends BaseRepository
{
    public function __construct(ScheduleCategory $model)
    {
        parent::__construct($model);
    }

    public function removeByScheduleId(int $scheduleId): bool
    {
        return $this->model->where('schedule_id', $scheduleId)->delete();
    }
}
