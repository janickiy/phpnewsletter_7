<?php

namespace App\Repositories;

use App\DTO\ReadySentCreateData;
use App\DTO\ReadySentReadData;
use App\Models\ReadySent;

class ReadySentRepository extends BaseRepository
{
    public function __construct(ReadySent $model)
    {
        parent::__construct($model);
    }

    public function add(ReadySentCreateData $data): ReadySent
    {
        return ReadySent::query()->create([
            'subscriber_id' => $data->subscriberId,
            'template_id' => $data->templateId,
            'success' => $data->success,
            'schedule_id' => $data->scheduleId,
            'log_id' => $data->logId,
            'email' => $data->email,
            'template' => $data->template,
            'errorMsg' => $data->errorMsg,
            'readMail' => $data->readMail,
        ]);
    }

    public function markAsRead(ReadySentReadData $data): bool
    {
        return $this->model->query()
            ->where('template_id', $data->templateId)
            ->where('subscriber_id', $data->subscriberId)
            ->update([
                'readMail' => $data->readMail,
            ]);
    }

    public function update(int $id, array $data): bool
    {
        return parent::update($id, $data);
    }

    public function countStatus(int $logId, int $success): int
    {
        return $this->model->where('log_id', $logId)->where('success', $success)->count();
    }

    /**
     * @return array|false[]
     */
    public function logOnline(int $limit = 5): array
    {
        $readySent = $this->model
            ->orderBy('id', 'desc')
            ->where('log_id', '>', 0)
            ->limit($limit)
            ->get();

        if (! $readySent) {
            return ['result' => false];
        }

        $rows = [];

        foreach ($readySent as $row) {
            $rows[] = [
                'subscriber_id' => $row->subscriber_id,
                'email' => $row->email,
                'status' => $row->success === 1 ? __('frontend.str.sent') : __('frontend.str.not_sent'),
            ];
        }

        return [
            'result' => true,
            'item' => $rows,
        ];
    }
}
