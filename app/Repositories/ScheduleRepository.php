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
    public function __construct(
        Schedule $model,
        private readonly DatabaseManager $database
    ) {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return Schedule
     * @throws \Throwable
     */
    public function add(array $data): Schedule
    {
        return $this->database->transaction(function () use ($data) {
            $model = $this->create($this->mapping($data));

            $this->syncCategories($model->id, $data['categoryId'] ?? []);

            return $model;
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return parent::update($id, $data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \Throwable
     */
    public function updateWithMapping(int $id, array $data): bool
    {
        return $this->database->transaction(function () use ($id, $data) {
            ScheduleCategory::where('schedule_id', $id)->delete();

            $this->syncCategories($id, $data['categoryId'] ?? []);

            return parent::update($id, $this->mapping($data));
        });
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

            if (!$model) {
                return false;
            }

            ScheduleCategory::where('schedule_id', $id)->delete();

            return $model->delete();
        });
    }

    /**
     * @return Collection|null
     */
    public function getScheduleEvent(): ?Collection
    {
        return $this->model
            ->where('event_start', '<=', Carbon::now()->toDateTimeString())
            ->where('event_end', '>=', Carbon::now()->toDateTimeString())
            ->get();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getScheduleByDateInterval(Request $request): array
    {
        $rows = $this->model
            ->whereDate('event_start', '>=', $request->start)
            ->whereDate('event_end', '<=', $request->end)
            ->get(['id', 'event_name', 'event_start', 'event_end']);

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id' => $row->id,
                'start' => $row->event_start,
                'end' => $row->event_end,
                'title' => $row->event_name,
            ];
        }

        return $items;
    }

    /**
     * @param int $id
     * @return bool|null
     * @throws \Throwable
     */
    public function remove(int $id): ?bool
    {
        return $this->database->transaction(function () use ($id) {
            ScheduleCategory::where('schedule_id', $id)->delete();

            return $this->delete($id);
        });
    }

    /**
     * @param int $scheduleId
     * @param array $categoryIds
     * @return void
     */
    private function syncCategories(int $scheduleId, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            if (is_numeric($categoryId)) {
                ScheduleCategory::create([
                    'schedule_id' => $scheduleId,
                    'category_id' => (int) $categoryId,
                ]);
            }
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        [$eventStart, $eventEnd] = $this->resolveEventDates($data);

        return collect($data)
            ->merge([
                'event_start' => $eventStart,
                'event_end' => $eventEnd,
            ])
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                return match ($key) {
                    'template_id' => !is_null($value) ? (int) $value : null,
                    default => $value,
                };
            })
            ->all();
    }


    /**
     * @param array $data
     * @return array|null[]
     */
    private function resolveEventDates(array $data): array
    {
        if (
            !empty($data['event_start']) &&
            !empty($data['event_end'])
        ) {
            return [
                Carbon::createFromFormat('d.m.Y H:i', $data['event_start'])->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('d.m.Y H:i', $data['event_end'])->format('Y-m-d H:i:s'),
            ];
        }

        if (!empty($data['date_interval']) && str_contains($data['date_interval'], ' - ')) {
            [$eventStart, $eventEnd] = explode(' - ', $data['date_interval'], 2);

            return [
                Carbon::createFromFormat('d.m.Y H:i', $eventStart)->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('d.m.Y H:i', $eventEnd)->format('Y-m-d H:i:s'),
            ];
        }

        return [null, null];
    }
}
