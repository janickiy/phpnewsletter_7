<?php

namespace App\Repositories;

use App\DTO\SubscriberCreateData;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class SubscriberRepository extends BaseRepository
{
    public function __construct(
        Subscribers $model,
        private readonly DatabaseManager $database
    ) {
        parent::__construct($model);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWithMapping(int $id, array $data): bool
    {
        return parent::update($id, $this->mapping($data));
    }

    /**
     * @param array $data
     * @return Subscribers|null
     * @throws \Throwable
     */
    public function add(array $data): ?Subscribers
    {
        return $this->database->transaction(function () use ($data) {
            $model = $this->model->create($this->mapping($data));

            if (!$model) {
                return null;
            }

            $this->syncSubscriptions($model->id, $data['categoryId'] ?? []);

            return $model;
        });
    }

    /**
     * @param SubscriberCreateData $data
     * @return Subscribers
     * @throws \Throwable
     */
    public function createFrontendSubscriber(SubscriberCreateData $data): Subscribers
    {
        return $this->database->transaction(function () use ($data) {
            $subscriber = $this->model->create($data->toArray());

            $this->syncSubscriptions($subscriber->id, $data->categoryIds);

            return $subscriber;
        });
    }

    /**
     * @param int $logId
     * @param int $templateId
     * @param array $categoryId
     * @param int $order
     * @param int $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribers(
        int $logId,
        int $templateId,
        array $categoryId,
        int $order,
        int $limit,
        ?string $interval = null
    ): ?Collection {
        $q = $this->model->select('subscribers.email', 'subscribers.token', 'subscribers.id', 'subscribers.name')
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->leftJoin('ready_sent', function ($join) use ($templateId, $logId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.template_id', $templateId)
                    ->where('ready_sent.log_id', $logId)
                    ->where(function ($query) {
                        $query->where('ready_sent.success', 1)
                            ->orWhere('ready_sent.success', 0);
                    });
            })
            ->whereIn('subscriptions.category_id', $categoryId)
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw((string) $order)
            ->take($limit)
            ->get();
    }

    /**
     * @param array $categoryId
     * @param int $limit
     * @param string|null $interval
     * @return int
     */
    public function countSubscriptions(array $categoryId, int $limit, ?string $interval = null): int
    {
        $q = Subscriptions::query()
            ->select('subscribers.id')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->where('subscribers.active', 1)
            ->whereIn('subscriptions.category_id', $categoryId);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->groupBy('subscribers.id')
            ->take($limit)
            ->get()
            ->count();
    }

    /**
     * @param int $scheduleId
     * @param string $order
     * @param int|null $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribersNotReadySent(
        int $scheduleId,
        string $order,
        ?int $limit = null,
        ?string $interval = null
    ): ?Collection {
        $q = $this->model->select([
            'subscribers.email',
            'subscribers.id',
            'subscribers.token',
            'subscribers.name',
        ])
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->join('schedule_category', function ($join) use ($scheduleId) {
                $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                    ->where('schedule_category.schedule_id', $scheduleId);
            })
            ->leftJoin('ready_sent', function ($join) use ($scheduleId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.schedule_id', $scheduleId)
                    ->where(function ($query) {
                        $query->where('ready_sent.success', 1)
                            ->orWhere('ready_sent.success', 0);
                    });
            })
            ->whereNull('ready_sent.subscriber_id')
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->take($limit)
            ->get();
    }

    /**
     * @param int $scheduleId
     * @param string $order
     * @param int|null $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribersUnSent(
        int $scheduleId,
        string $order,
        ?int $limit = null,
        ?string $interval = null
    ): ?Collection {
        $q = $this->model->select([
            'subscribers.email',
            'subscribers.id',
            'subscribers.token',
            'subscribers.name',
        ])
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->join('schedule_category', function ($join) use ($scheduleId) {
                $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                    ->where('schedule_category.schedule_id', $scheduleId);
            })
            ->leftJoin('ready_sent', function ($join) use ($scheduleId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.schedule_id', $scheduleId)
                    ->where('ready_sent.success', 0);
            })
            ->whereNull('ready_sent.subscriber_id')
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->limit($limit)
            ->get();
    }

    /**
     * @param int $subscriberId
     * @return array
     */
    public function getSubscriberCategoryIdList(int $subscriberId): array
    {
        return Subscriptions::query()
            ->where('subscriber_id', $subscriberId)
            ->pluck('category_id')
            ->toArray();
    }

    /**
     * @param int $action
     * @param array $ids
     * @return void
     */
    public function updateStatus(int $action, array $ids = []): void
    {
        switch ($action) {
            case 0:
            case 1:
                $this->model->whereIn('id', $ids)->update(['active' => $action]);
                break;
            case 2:
                Subscriptions::query()->whereIn('subscriber_id', $ids)->delete();
                $this->model->whereIn('id', $ids)->delete();
                break;
        }
    }

    /**
     * @param int $subscriberId
     * @param array $categoryIds
     * @return void
     */
    private function syncSubscriptions(int $subscriberId, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            if (!is_numeric($categoryId)) {
                continue;
            }

            Subscriptions::query()->create([
                'subscriber_id' => $subscriberId,
                'category_id' => (int) $categoryId,
            ]);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->all();
    }
}
