<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Models\ScheduleCategory;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class ScheduleRepository extends BaseRepository
{
    public function __construct(Schedule $model, private readonly DatabaseManager $database)
    {
        parent::__construct($model);
    }

    /**
     * @param int $id
     * @param array $data
     * @return Schedule|null
     */
    public function update(int $id, array $data): ?Schedule
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
     * @return bool|null
     * @throws \Throwable
     */
    public function removeSchedule(int $id): ?bool
    {
        return $this->database->transaction(function () use ($id) {
            $model = $this->model->find($id);

            if (!$model) return false;

            $this->model->delete();
            ScheduleCategory::where('schedule_id', $id)->delete();

            return true;
        });
    }

    /**
     * @return Collection|null
     */
    public function getScheduleEvent(): ?Collection
    {
        return $this->model->where('event_start' , '<=' , Carbon::now()->toDateTimeString())
            ->where('event_end', '>=', Carbon::now()->toDateTimeString())
            ->get();
    }

}
