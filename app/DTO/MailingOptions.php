<?php

namespace App\DTO;

final class MailingOptions
{
    public function __construct(
        public readonly string  $order,
        public readonly ?int    $limit,
        public readonly ?string $interval,
    )
    {
    }

    public static function fromValues(
        bool   $randomOrder,
        bool   $limitEnabled,
        int    $limit,
        string $intervalType,
        int    $intervalNumber,
    ): self
    {
        return new self(
            order: $randomOrder ? 'RAND()' : 'subscribers.id',
            limit: $limitEnabled ? $limit : null,
            interval: self::resolveInterval($intervalType, $intervalNumber),
        );
    }

    public function limitReached(int $sentCount): bool
    {
        return $this->limit !== null && $sentCount >= $this->limit;
    }

    private static function resolveInterval(string $type, int $number): ?string
    {
        if ($number <= 0) {
            return null;
        }

        $unit = match ($type) {
            'minute' => 'MINUTE',
            'hour' => 'HOUR',
            'day' => 'DAY',
            default => null,
        };

        if ($unit === null) {
            return null;
        }

        return "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '{$number}' {$unit})";
    }
}
