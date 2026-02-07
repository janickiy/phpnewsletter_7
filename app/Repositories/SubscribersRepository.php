<?php

namespace App\Repositories;

use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Support\Collection;


class SubscribersRepository extends BaseRepository
{
    public function __construct(Subscribers $model)
    {
        parent::__construct($model);
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

        return $q->groupBy('subscribers.id')
            ->groupBy('subscribers.email')
            ->groupBy('subscribers.token')
            ->groupBy('subscribers.name')
            ->orderByRaw($order)
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
        $q = $this->model->select(['subscribers.email', 'subscribers.id', 'subscribers.token', 'subscribers.name'])
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

        return $q->groupBy('subscribers.id')
            ->groupBy('subscribers.email')
            ->groupBy('subscribers.token')
            ->groupBy('subscribers.name')
            ->orderByRaw($order)
            ->take($limit)
            ->get();
    }

}
