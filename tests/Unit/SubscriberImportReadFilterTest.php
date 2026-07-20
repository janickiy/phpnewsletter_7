<?php

namespace Tests\Unit;

use App\Services\SubscriberImportReadFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SubscriberImportReadFilterTest extends TestCase
{
    #[DataProvider('cellProvider')]
    public function test_it_reads_only_import_columns_within_the_chunk(
        string $column,
        int $row,
        bool $expected,
    ): void {
        $filter = new SubscriberImportReadFilter(10, 20);

        $this->assertSame($expected, $filter->readCell($column, $row));
    }

    public static function cellProvider(): array
    {
        return [
            'first email cell' => ['A', 10, true],
            'last name cell' => ['B', 20, true],
            'row before chunk' => ['A', 9, false],
            'row after chunk' => ['B', 21, false],
            'unneeded column' => ['C', 15, false],
        ];
    }
}
