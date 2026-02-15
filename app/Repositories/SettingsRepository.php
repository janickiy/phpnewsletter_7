<?php

namespace App\Repositories;

use App\Models\CustomHeaders;
use App\Models\Settings;

class SettingsRepository extends BaseRepository
{
    public function __construct(Settings $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return void
     */
    public function setSettings(array $data): void
    {
        $array = [];
        $array['REQUIRE_SUB_CONFIRMATION'] = $data['REQUIRE_SUB_CONFIRMATION'] ? 1 : 0;
        $array['SHOW_UNSUBSCRIBE_LINK'] = $data['SHOW_UNSUBSCRIBE_LINK']  ? 1 : 0;
        $array['REQUEST_REPLY'] = $data['REQUEST_REPLY'] ? 1 : 0;
        $array['NEW_SUBSCRIBER_NOTIFY'] = $data['NEW_SUBSCRIBER_NOTIFY'] ? 1 : 0;
        $array['RANDOM_SEND'] = $data['RANDOM_SEND']  ? 1 : 0;
        $array['RENDOM_REPLACEMENT_SUBJECT'] = $data['RENDOM_REPLACEMENT_SUBJECT'] ? 1 : 0;
        $array['RANDOM_REPLACEMENT_BODY'] = $data['RANDOM_REPLACEMENT_BODY'] ? 1 : 0;
        $array['ADD_DKIM'] = $data['ADD_DKIM'] ? 1 : 0;
        $array['LIMIT_SEND'] = $data['LIMIT_SEND']  ? 1 : 0;
        $array['REQUEST_REPLY'] = $data['REQUEST_REPLY']  ? 1 : 0;
        $array['REMOVE_SUBSCRIBER'] = $data['REMOVE_SUBSCRIBER']  ? 1 : 0;

        foreach ($array ?? [] as $key => $value) {
            $this->model->setValue($key, $value);
        }

        if ($data['header_name']) {
            Customheaders::truncate();

            for ($i = 0; $i < count($data['header_name']); $i++) {
                $name = $data['header_name'];
                $value = $data['header_value'];
                $name[$i] = trim($name[$i]);
                $value[$i] = trim($value[$i]);

                if (preg_match("/^[\-a-zA-Z]+$/", $name[$i])) {
                    $value[$i] = str_replace(';', '', $value[$i]);
                    $value[$i] = str_replace(':', '', $value[$i]);
                    if ($name[$i] && $value[$i]) {
                        $fields = [
                            'name' => $name[$i],
                            'value' => $value[$i]
                        ];

                        Customheaders::create($fields);
                    }
                }
            }
        } else {
            Customheaders::truncate();
        }
    }
}
