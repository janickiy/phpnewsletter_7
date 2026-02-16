<?php

namespace App\Repositories;

use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Support\Collection;


class SubscriberRepository extends BaseRepository
{
    public function __construct(Subscribers $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return Subscribers|null
     */
    public function add(array $data): ?Subscribers
    {
        $model = $this->model->create($data);

        if ($model) {
            foreach ($data['categoryId'] ?? [] as $categoryId) {
                if (is_numeric($categoryId)) {
                    Subscriptions::create(['subscriber_id' => $model->id, 'category_id' => $categoryId]);
                }
            }

            return $model;
        }

        return null;
    }

    /**
     * @param array $data
     * @param $id
     * @return bool
     */
    public function update(array $data, $id): bool
    {
        $model = $this->find($id);

        if ($model) {
            $model->name = $data['name'];
            $model->email = $data['email'];

            return $model->save();
        }

        return false;
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
    public function getSubscribers(int $logId, int $templateId, array $categoryId, int $order, int $limit, ?string $interval = null): ?Collection
    {
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
            ->whereIN('subscriptions.category_id', $categoryId)
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
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
        $q = Subscriptions::select('subscribers.id')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->where('subscribers.active', 1)
            ->whereIN('subscriptions.category_id', $categoryId);

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
     * @param int $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribersNotReadySent(int $scheduleId, string $order, ?int $limit = null, ?string $interval = null): ?Collection
    {
        $q = $this->model->select([
            'subscribers.email',
            'subscribers.id',
            'subscribers.token',
            'subscribers.name'])
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->join('schedule_category', function ($join) use ($scheduleId) {
                $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                    ->where('schedule_category.scheduleId', $scheduleId);
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
    public function getSubscribersUnSent(int $scheduleId, string $order, ?int $limit = null, ?string $interval = null): ?Collection
    {
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
     * @param int $subscriber_id
     * @return array
     */
    public function getSubscriberCategoryIdList(int $subscriber_id): array
    {
        $rows = Subscriptions::query()->where('subscriber_id', $subscriber_id)->get();

        $Ids = [];
        foreach ($rows->subscriptions as $subscription) {
            $Ids[] = $subscription->category_id;
        }

        return $Ids;
    }

    /**
     * @param int $action
     * @param array $Ids
     * @return void
     */
    public function updateStatus(int $action, array $Ids = []): void
    {
        switch ($action) {
            case  0 :
            case  1 :
                $this->model->whereIN('id', $Ids)->update(['active' => $action]);
                break;
            case 2 :
                Subscriptions::whereIN('subscriber_id', $Ids)->delete();
                $this->model->whereIN('id', $Ids)->delete();
                break;
        }
    }
}
