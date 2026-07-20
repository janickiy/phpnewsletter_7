<?php

namespace Tests\Unit;

use App\DTO\SubscriberImportData;
use PHPUnit\Framework\TestCase;

class SubscriberImportDataTest extends TestCase
{
    public function test_it_normalizes_transport_values_once(): void
    {
        $import = new SubscriberImportData(
            filePath: '/tmp/subscribers.xlsx',
            extension: ' .XLSX ',
            categoryIds: [2, '2', 0, -1, 'invalid', '3'],
            charset: ' Windows-1251 ',
        );

        $this->assertSame('/tmp/subscribers.xlsx', $import->filePath);
        $this->assertSame('xlsx', $import->extension);
        $this->assertSame([2, 3], $import->categoryIds);
        $this->assertSame('Windows-1251', $import->charset);
    }

    public function test_it_converts_an_empty_charset_to_null(): void
    {
        $import = new SubscriberImportData('/tmp/subscribers.txt', 'txt', [], '  ');

        $this->assertNull($import->charset);
    }
}
