<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Models\ScheduleCategory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ScheduleRepository extends BaseRepository
{
    public function __construct(Schedule $model, private readonly DatabaseManager $database)
    {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return Schedule
     */
    public function add(array $data): Schedule
    {
        $date = explode(' - ', $data['date_interval']);

        $model = $this->create(array_merge($data, [
            'event_start' => date("Y-m-d H:i:s", strtotime($date[0])),
            'event_end' => date("Y-m-d H:i:s", strtotime($date[1])),
        ]));

        foreach ($data['categoryId'] ?? [] as $categoryId) {
            if (is_numeric($categoryId)) {
                ScheduleCategory::create(['schedule_id' => $model->id, 'category_id' => $categoryId]);
            }
        }

        return $model;
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
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWithMapping(int $id, array $data): bool
    {
        ScheduleCategory::where('schedule_id', $data['id'])->delete();

        foreach ($data['categoryId'] ?? [] as $categoryId) {
            if (is_numeric($categoryId)) {
                ScheduleCategory::create(['schedule_id' => $data['id'], 'category_id' => $categoryId]);
            }
        }

        return $this->update($id, $this->mapping($data));
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
        return $this->model->where('event_start', '<=', Carbon::now()->toDateTimeString())
            ->where('event_end', '>=', Carbon::now()->toDateTimeString())
            ->get();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getScheduleByDateInterval(Request $request): array
    {
        $rows = Schedule::whereDate('event_start', '>=', $request->start)
            ->whereDate('event_end', '<=', $request->end)
            ->get(['id', 'event_name', 'event_start', 'event_end']);

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id' => $row->id,
                'start' => $row->event_start, // Format as ISO 8601 with time zone
                'end' => $row->event_end,
                'title' => $row->event_name,
            ];
        }

        return $items;
    }

    public function remove(int $id)
    {
        $this->delete($id);
        ScheduleCategory::where('schedule_id', $id)->delete();
    }

    private function mapping(array $data): array
    {
        $date = explode(' - ', $data['date_interval']);

        return collect($data)
            ->merge([
                'event_start' => date("Y-m-d H:i:s", strtotime($date[0])),
                'event_end' => date("Y-m-d H:i:s", strtotime($date[1])),
            ])
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if ($key === 'template_id' && !is_null($value)) {
                    return (int)$value;
                }
                return $value;
            })
            ->all();
    }
}
