<?php

namespace App\Repositories;

use App\Models\Subscriptions;

class SubscriptionRepository extends BaseRepository
{
    public function __construct(Subscriptions $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array|null $categoryIds
     * @param int $subscriberId
     * @return void
     */
    public function updateSubscriptions(?array $categoryIds, int $subscriberId): void
    {
        $this->model->where('subscriber_id', $subscriberId)->delete();

        foreach ($categoryIds ?? [] as $categoryId) {
            if (is_numeric($categoryId)) $this->create(['subscriber_id' => $subscriberId, 'category_id' => $categoryId]);
        }
    }

    /**
     * @param int $subscriberId
     * @return bool
     */
    public function removeBySubscriberId(int $subscriberId): bool
    {
        return $this->model->where('subscriber_id', $subscriberId)->delete();
    }
}
