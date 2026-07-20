<?php

namespace Tests\Feature;

use App\DTO\MailingOptions;
use App\Enums\ProcessStatus;
use App\Models\ReadySent;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\ProcessRepository;
use App\Repositories\ReadySentRepository;
use App\Repositories\SubscriberRepository;
use App\Services\MailingOptionsResolver;
use App\Services\MailSender;
use App\Services\SendMailService;
use App\Services\SubscriberSentTimeUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Matcher\Closure;
use RuntimeException;
use Tests\TestCase;

class SendMailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pause_flushes_timestamps_for_already_sent_messages(): void
    {
        [$template, $subscribers] = $this->mailingRecords();
        $dependencies = $this->dependencies($subscribers);

        $dependencies['process']->shouldReceive('getProcess')
            ->twice()
            ->andReturn(ProcessStatus::Start->value, ProcessStatus::Pause->value);
        $dependencies['sender']->shouldReceive('sendTemplate')
            ->once()
            ->with($this->sameModelAs($template), $subscribers[0])
            ->andReturn(['result' => true, 'error' => null]);
        $dependencies['deliveries']->shouldReceive('add')
            ->once()
            ->andReturn(new ReadySent);
        $this->expectTimestampFlush($dependencies['updater'], $subscribers[0]->id);

        $result = $this->service($dependencies)->sendOut($this->requestFor($template));

        $this->assertSame(['result' => true, 'completed' => true], $result);
    }

    public function test_exception_flushes_timestamps_and_resets_process_status(): void
    {
        [$template, $subscribers] = $this->mailingRecords();
        $dependencies = $this->dependencies($subscribers);

        $dependencies['process']->shouldReceive('getProcess')
            ->twice()
            ->andReturn(ProcessStatus::Start->value);
        $dependencies['process']->shouldReceive('updateByUserId')
            ->once()
            ->with(100, ProcessStatus::Stop->value);
        $dependencies['sender']->shouldReceive('sendTemplate')
            ->once()
            ->with($this->sameModelAs($template), $subscribers[0])
            ->andReturn(['result' => true, 'error' => null]);
        $dependencies['sender']->shouldReceive('sendTemplate')
            ->once()
            ->with($this->sameModelAs($template), $subscribers[1])
            ->andThrow(new RuntimeException('SMTP connection failed'));
        $dependencies['deliveries']->shouldReceive('add')
            ->once()
            ->andReturn(new ReadySent);
        $this->expectTimestampFlush($dependencies['updater'], $subscribers[0]->id);

        $this->expectException(RuntimeException::class);
        $this->service($dependencies)->sendOut($this->requestFor($template));
    }

    /**
     * @return array{Templates, list<Subscribers>}
     */
    private function mailingRecords(): array
    {
        $template = Templates::query()->create([
            'name' => 'Service test',
            'body' => '<p>Body</p>',
            'prior' => 0,
        ]);
        $subscribers = [
            $this->subscriber(101, 'first@example.com'),
            $this->subscriber(102, 'second@example.com'),
        ];

        $user = new User([
            'name' => 'Mailer',
            'login' => 'mailer',
            'role' => User::ROLE_ADMIN,
            'password' => 'unused',
        ]);
        $user->id = 100;
        $user->exists = true;
        $this->actingAs($user);

        return [$template, $subscribers];
    }

    /**
     * @param  list<Subscribers>  $subscribers
     * @return array<string, object>
     */
    private function dependencies(array $subscribers): array
    {
        $deliveries = Mockery::mock(ReadySentRepository::class);
        $subscriberRepository = Mockery::mock(SubscriberRepository::class);
        $subscriberRepository->shouldReceive('getSubscribers')
            ->once()
            ->andReturn(collect($subscribers));

        $process = Mockery::mock(ProcessRepository::class);
        $process->shouldReceive('updateByUserId')
            ->once()
            ->with(100, ProcessStatus::Start->value);

        $sender = Mockery::mock(MailSender::class);
        $options = Mockery::mock(MailingOptionsResolver::class);
        $options->shouldReceive('resolve')
            ->once()
            ->andReturn(new MailingOptions('subscribers.id', null, null));

        return [
            'deliveries' => $deliveries,
            'subscribers' => $subscriberRepository,
            'process' => $process,
            'sender' => $sender,
            'options' => $options,
            'updater' => Mockery::mock(SubscriberSentTimeUpdater::class),
        ];
    }

    private function service(array $dependencies): SendMailService
    {
        return new SendMailService(
            $dependencies['deliveries'],
            $dependencies['subscribers'],
            $dependencies['process'],
            $dependencies['sender'],
            $dependencies['options'],
            $dependencies['updater'],
        );
    }

    private function requestFor(Templates $template): Request
    {
        return Request::create('/ajax', 'POST', [
            'templateId' => [$template->id],
            'categoryId' => [1],
            'logId' => 1,
        ]);
    }

    private function subscriber(int $id, string $email): Subscribers
    {
        $subscriber = new Subscribers([
            'name' => 'Subscriber '.$id,
            'email' => $email,
            'token' => str_repeat((string) ($id % 10), 32),
        ]);
        $subscriber->id = $id;
        $subscriber->exists = true;

        return $subscriber;
    }

    private function sameModelAs(Templates $template): Closure
    {
        return Mockery::on(
            fn (Templates $actual): bool => $actual->is($template),
        );
    }

    private function expectTimestampFlush(
        SubscriberSentTimeUpdater $updater,
        int $subscriberId,
    ): void {
        $updater->shouldReceive('update')
            ->once()
            ->with(Mockery::on(
                fn (array $updates): bool => array_keys($updates) === [$subscriberId]
                    && is_string($updates[$subscriberId]),
            ));
    }
}
