<?php

namespace App\DTO;

use App\Models\Subscribers;
use App\Models\Templates;

final class MailMessageData
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $email,
        public readonly int $prior = 0,
        public readonly ?string $name = 'USERNAME',
        public readonly int $templateId = 0,
        public readonly int $subscriberId = 0,
        public readonly string $token = '',
        public readonly bool $tracking = true,
        public readonly bool $unsubscribe = true,
    ) {}

    public static function fromTemplateAndSubscriber(
        Templates $template,
        Subscribers $subscriber
    ): self {
        return new self(
            subject: (string) $template->name,
            body: (string) $template->body,
            email: (string) $subscriber->email,
            prior: (int) $template->prior,
            name: $subscriber->name,
            templateId: (int) $template->id,
            subscriberId: (int) $subscriber->id,
            token: (string) $subscriber->token,
        );
    }
}
