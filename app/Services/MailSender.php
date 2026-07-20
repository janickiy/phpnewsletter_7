<?php

namespace App\Services;

use App\DTO\MailMessageData;
use App\Helpers\SendEmailHelper;
use App\Models\Subscribers;
use App\Models\Templates;
use PHPMailer\PHPMailer\Exception;

class MailSender
{
    public function __construct(
        private readonly SmtpConfigurationResolver $smtpConfigurationResolver,
    ) {}

    /**
     * @throws Exception
     */
    public function send(MailMessageData $message, ?int $attachmentTemplateId = null): array
    {
        $helper = $this->createHelper();
        $helper->subject = $message->subject;
        $helper->body = $message->body;
        $helper->email = $message->email;
        $helper->prior = $message->prior;
        $helper->name = $message->name;
        $helper->templateId = $message->templateId;
        $helper->subscriberId = $message->subscriberId;
        $helper->token = $message->token;
        $helper->tracking = $message->tracking;
        $helper->unsub = $message->unsubscribe;

        return $helper->sendEmail($attachmentTemplateId);
    }

    /**
     * @throws Exception
     */
    public function sendTemplate(Templates $template, Subscribers $subscriber): array
    {
        return $this->send(MailMessageData::fromTemplateAndSubscriber($template, $subscriber));
    }

    protected function createHelper(): SendEmailHelper
    {
        return new SendEmailHelper($this->smtpConfigurationResolver);
    }
}
