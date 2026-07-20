<?php

namespace Tests\Unit;

use App\DTO\MailingOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MailingOptionsTest extends TestCase
{
    public function test_it_resolves_selection_options(): void
    {
        $options = MailingOptions::fromValues(
            randomOrder: true,
            limitEnabled: true,
            limit: 25,
            intervalType: 'hour',
            intervalNumber: 3,
        );

        $this->assertSame('RAND()', $options->order);
        $this->assertSame(25, $options->limit);
        $this->assertSame(
            "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '3' HOUR)",
            $options->interval,
        );
        $this->assertFalse($options->limitReached(24));
        $this->assertTrue($options->limitReached(25));
    }

    public function test_it_disables_optional_filters(): void
    {
        $options = MailingOptions::fromValues(
            randomOrder: false,
            limitEnabled: false,
            limit: 10,
            intervalType: 'week',
            intervalNumber: 1,
        );

        $this->assertSame('subscribers.id', $options->order);
        $this->assertNull($options->limit);
        $this->assertNull($options->interval);
        $this->assertFalse($options->limitReached(100));
    }

    #[DataProvider('intervalProvider')]
    public function test_it_builds_supported_intervals(string $type, string $unit): void
    {
        $options = MailingOptions::fromValues(false, false, 0, $type, 2);

        $this->assertStringEndsWith("INTERVAL '2' {$unit})", $options->interval);
    }

    public static function intervalProvider(): array
    {
        return [
            'minutes' => ['minute', 'MINUTE'],
            'hours' => ['hour', 'HOUR'],
            'days' => ['day', 'DAY'],
        ];
    }

    public function test_it_ignores_non_positive_interval(): void
    {
        $options = MailingOptions::fromValues(false, false, 0, 'minute', 0);

        $this->assertNull($options->interval);
    }
}
