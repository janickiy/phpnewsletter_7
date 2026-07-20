<?php

namespace Tests\Unit;

use App\DTO\MailMessageData;
use App\Helpers\SendEmailHelper;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Services\MailSender;
use App\Services\SmtpConfigurationResolver;
use App\Services\UrlHostResolver;
use PHPUnit\Framework\TestCase;

class MailSenderTest extends TestCase
{
    public function test_it_maps_message_data_to_the_legacy_helper(): void
    {
        $helper = new class(new SmtpConfigurationResolver, new UrlHostResolver) extends SendEmailHelper
        {
            public ?int $receivedAttachmentTemplateId = null;

            public function sendEmail(?int $attach = null): array
            {
                $this->receivedAttachmentTemplateId = $attach;

                return ['result' => true, 'error' => null];
            }
        };

        $sender = new class($helper) extends MailSender
        {
            public function __construct(private readonly SendEmailHelper $helper) {}

            protected function createHelper(): SendEmailHelper
            {
                return $this->helper;
            }
        };

        $result = $sender->send(new MailMessageData(
            subject: 'Subject',
            body: 'Body',
            email: 'user@example.com',
            prior: 2,
            name: 'User',
            templateId: 10,
            subscriberId: 20,
            token: 'token',
            tracking: false,
            unsubscribe: false,
        ), 10);

        $this->assertTrue($result['result']);
        $this->assertSame('Subject', $helper->subject);
        $this->assertSame('Body', $helper->body);
        $this->assertSame('user@example.com', $helper->email);
        $this->assertSame(2, $helper->prior);
        $this->assertSame('User', $helper->name);
        $this->assertSame(10, $helper->templateId);
        $this->assertSame(20, $helper->subscriberId);
        $this->assertSame('token', $helper->token);
        $this->assertFalse($helper->tracking);
        $this->assertFalse($helper->unsub);
        $this->assertSame(10, $helper->receivedAttachmentTemplateId);
    }

    public function test_it_builds_a_message_from_template_and_subscriber(): void
    {
        $template = new Templates([
            'name' => 'Newsletter',
            'body' => '<p>Hello</p>',
            'prior' => 1,
        ]);
        $template->id = 7;

        $subscriber = new Subscribers([
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'token' => 'subscriber-token',
        ]);
        $subscriber->id = 42;

        $message = MailMessageData::fromTemplateAndSubscriber($template, $subscriber);

        $this->assertSame('Newsletter', $message->subject);
        $this->assertSame('<p>Hello</p>', $message->body);
        $this->assertSame('alex@example.com', $message->email);
        $this->assertSame('Alex', $message->name);
        $this->assertSame(7, $message->templateId);
        $this->assertSame(42, $message->subscriberId);
        $this->assertSame('subscriber-token', $message->token);
    }
}
