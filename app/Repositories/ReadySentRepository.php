<?php

namespace App\Repositories;

use App\Models\ReadySent;
use App\DTO\ReadySentCreateData;

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

    /**
     * @param int $id
     * @param array $data
     * @return ReadySent|null
     */
    public function update(int $id, array $data): ?ReadySent
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
     * @param int $logId
     * @param int $success
     * @return int
     */
    public function countStatus(int $logId, int $success): int
    {
        return $this->model->where('log_id', $logId)->where('success', $success)->count();
    }

    /**
     * @param int $limit
     * @return array|false[]
     */
    public function logOnline(int $limit = 5): array
    {
        $readySent = $this->model
            ->orderBy('id', 'desc')
            ->where('log_id', '>', 0)
            ->limit($limit)
            ->get();

        if ($readySent) {
            return [
                'result' => false,
            ];
        }
        $rows = [];

        foreach ($readySent ?? [] as $row) {
            $rows[] = [
                'subscriber_id' => $row->subscriber_id,
                "email" => $row->email,
                "status" => $row->success == 1 ? trans('frontend.str.sent') : trans('frontend.str.not_sent'),
            ];
        }

        return [
            'result' => true,
            'item' => $rows
        ];
    }


}
