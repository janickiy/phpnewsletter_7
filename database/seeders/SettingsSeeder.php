<?php

namespace Database\Seeders;

use App\Models\{Settings};
use App\Helpers\StringHelpers;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data_insert['en'][] = ['name' => 'EMAIL', 'value' => 'vasya-pupkin@my-domain.com'];
        $data_insert['en'][] = ['name' => 'FROM',  'value' => 'my-domain.com'];
        $data_insert['en'][] = ['name' => 'RETURN_PATH', 'value' => ''];
        $data_insert['en'][] = ['name' => 'LIST_OWNER', 'value' => ''];
        $data_insert['en'][] = ['name' => 'ORGANIZATION', 'value' => ''];
        $data_insert['en'][] = ['name' => 'SUBJECT_TEXT_CONFIRM', 'value' => 'Newsletter subscription'];
        $data_insert['en'][] = ['name' => 'TEXT_CONFIRMATION', 'value' => "Hello, %NAME%\r\n\r\nReceiving a newsletter is possible after the completion of activation. To activate your subscription, click on the following link: %CONFIRM%\r\n\r\nIf you have not subscribed to this email, just ignore this email or follow the link: %UNSUB%\r\n\r\nSincerely, \r\nteam %SERVER_NAME%"];
        $data_insert['en'][] = ['name' => 'REQUIRE_SUB_CONFIRMATION', 'value' => 1];
        $data_insert['en'][] = ['name' => 'UNSUBLINK', 'value' => 'Unsubscribe: <a href=%UNSUB%>%UNSUB%</a>'];
        $data_insert['en'][] = ['name' => 'SHOW_UNSUBSCRIBE_LINK', 'value' => '1'];
        $data_insert['en'][] = ['name' => 'REQUEST_REPLY', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'NEW_SUBSCRIBER_NOTIFY', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'SLEEP','value' => '0'];
        $data_insert['en'][] = ['name' => 'LIMIT_NUMBER', 'value' => '300'];
        $data_insert['en'][] = ['name' => 'LIMIT_SEND', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'DAYS_FOR_REMOVE_SUBSCRIBER', 'value' => '7'];
        $data_insert['en'][] = ['name' => 'REMOVE_SUBSCRIBER', 'value' =>'0'];
        $data_insert['en'][] = ['name' => 'RANDOM_SEND', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'RENDOM_REPLACEMENT_SUBJECT', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'RANDOM_REPLACEMENT_BODY', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'PRECEDENCE', 'value' => 'bulk'];
        $data_insert['en'][] = ['name' => 'CHARSET', 'value' => 'utf-8'];
        $data_insert['en'][] = ['name' => 'CONTENT_TYPE', 'value' => 'html'];
        $data_insert['en'][] = ['name' => 'HOW_TO_SEND', 'value' => 'php'];
        $data_insert['en'][] = ['name' => 'SENDMAIL_PATH', 'value' => '/usr/sbin/sendmail'];
        $data_insert['en'][] = ['name' => 'URL', 'value' => StringHelpers::getUrl()];
        $data_insert['en'][] = ['name' => 'ADD_DKIM', 'value' => '0'];
        $data_insert['en'][] = ['name' => 'DKIM_DOMAIN', 'value' => 'my-domain.com'];
        $data_insert['en'][] = ['name' => 'DKIM_SELECTOR', 'value' => 'phpnewsletter'];
        $data_insert['en'][] = ['name' => 'DKIM_PRIVATE', 'value' => ''];
        $data_insert['en'][] = ['name' => 'DKIM_PASSPHRASE', 'value' => 'password'];
        $data_insert['en'][] = ['name' => 'DKIM_IDENTITY',  'value' => ''];
        $data_insert['en'][] = ['name' => 'INTERVAL_TYPE', 'value' => 'no'];
        $data_insert['en'][] = ['name' => 'INTERVAL_NUMBER', 'value' => '1'];
        $data_insert['ru'][] = ['name' => 'EMAIL', 'value' => 'vasya-pupkin@my-domain.com'];
        $data_insert['ru'][] = ['name' => 'FROM',  'value' => 'my-domain.com'];
        $data_insert['ru'][] = ['name' => 'RETURN_PATH', 'value' => ''];
        $data_insert['ru'][] = ['name' => 'LIST_OWNER', 'value' => ''];
        $data_insert['ru'][] = ['name' => 'ORGANIZATION', 'value' => ''];
        $data_insert['ru'][] = ['name' => 'SUBJECT_TEXT_CONFIRM', 'value' => 'Подписка на рассылку'];
        $data_insert['ru'][] = ['name' => 'TEXT_CONFIRMATION', 'value' => "Здравствуйте, %NAME%\r\n\r\nПолучение рассылки возможно после завершения этапа активации подписки. Чтобы активировать подписку, перейдите по следующей ссылке: %CONFIRM%\r\n\r\nЕсли Вы не производили подписку на данный email, просто проигнорируйте это письмо или перейдите по ссылке: %UNSUB%\r\n\r\nС уважением, \r\nадминистратор сайта %SERVER_NAME%"];
        $data_insert['ru'][] = ['name' => 'REQUIRE_SUB_CONFIRMATION', 'value' => 1];
        $data_insert['ru'][] = ['name' => 'UNSUBLINK', 'value' => 'Отписаться от рассылки: <a href=%UNSUB%>%UNSUB%</a>'];
        $data_insert['ru'][] = ['name' => 'SHOW_UNSUBSCRIBE_LINK', 'value' => '1'];
        $data_insert['ru'][] = ['name' => 'REQUEST_REPLY', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'NEW_SUBSCRIBER_NOTIFY', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'SLEEP','value' => '0'];
        $data_insert['ru'][] = ['name' => 'LIMIT_NUMBER', 'value' => '300'];
        $data_insert['ru'][] = ['name' => 'LIMIT_SEND', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'DAYS_FOR_REMOVE_SUBSCRIBER', 'value' => '7'];
        $data_insert['ru'][] = ['name' => 'REMOVE_SUBSCRIBER', 'value' =>'0'];
        $data_insert['ru'][] = ['name' => 'RANDOM_SEND', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'RENDOM_REPLACEMENT_SUBJECT', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'RANDOM_REPLACEMENT_BODY', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'PRECEDENCE', 'value' => 'bulk'];
        $data_insert['ru'][] = ['name' => 'CHARSET', 'value' => 'utf-8'];
        $data_insert['ru'][] = ['name' => 'CONTENT_TYPE', 'value' => 'html'];
        $data_insert['ru'][] = ['name' => 'HOW_TO_SEND', 'value' => 'php'];
        $data_insert['ru'][] = ['name' => 'SENDMAIL_PATH', 'value' => '/usr/sbin/sendmail'];
        $data_insert['ru'][] = ['name' => 'URL', 'value' => StringHelpers::getUrl()];
        $data_insert['ru'][] = ['name' => 'ADD_DKIM', 'value' => '0'];
        $data_insert['ru'][] = ['name' => 'DKIM_DOMAIN', 'value' => 'my-domain.com'];
        $data_insert['ru'][] = ['name' => 'DKIM_SELECTOR', 'value' => 'phpnewsletter'];
        $data_insert['ru'][] = ['name' => 'DKIM_PRIVATE', 'value' => ''];
        $data_insert['ru'][] = ['name' => 'DKIM_PASSPHRASE', 'value' => 'password'];
        $data_insert['ru'][] = ['name' => 'DKIM_IDENTITY',  'value' => ''];
        $data_insert['ru'][] = ['name' => 'INTERVAL_TYPE', 'value' => 'no'];
        $data_insert['ru'][] = ['name' => 'INTERVAL_NUMBER', 'value' => '1'];

        foreach ($data_insert[config('app.locale')] as $row) {
            Settings::create(['name' => $row['name'], 'value' => $row['value']]);
        }
    }
}
