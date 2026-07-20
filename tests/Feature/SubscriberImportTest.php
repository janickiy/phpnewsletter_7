<?php

namespace Tests\Feature;

use App\DTO\SubscriberImportData;
use App\Services\SubscriberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubscriberImportTest extends TestCase
{
    use RefreshDatabase;

    private ?string $importFile = null;

    protected function tearDown(): void
    {
        if ($this->importFile !== null) {
            @unlink($this->importFile);
        }

        parent::tearDown();
    }

    public function test_text_import_uses_explicit_data_and_syncs_categories(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Imported',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->importFile = tempnam(sys_get_temp_dir(), 'subscriber-import-');
        $content = "JANE@EXAMPLE.COM Анна Иванова\nневалидная строка\njane@example.com Обновлённое имя\n";
        $encodedContent = iconv('UTF-8', 'Windows-1251', $content);

        $this->assertNotFalse($encodedContent);
        file_put_contents($this->importFile, $encodedContent);

        $progress = [];
        $result = app(SubscriberService::class)->importTextFile(
            new SubscriberImportData(
                filePath: $this->importFile,
                extension: 'txt',
                categoryIds: [$categoryId],
                charset: 'Windows-1251',
            ),
            static function (int $count) use (&$progress): void {
                $progress[] = $count;
            },
        );

        $this->assertSame(1, $result);
        $this->assertSame([1], $progress);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'jane@example.com',
            'name' => 'Обновлённое имя',
            'active' => 1,
        ]);

        $subscriberId = DB::table('subscribers')
            ->where('email', 'jane@example.com')
            ->value('id');

        $this->assertDatabaseHas('subscriptions', [
            'subscriber_id' => $subscriberId,
            'category_id' => $categoryId,
        ]);
    }

    public function test_spreadsheet_import_uses_the_same_explicit_contract(): void
    {
        $this->importFile = tempnam(sys_get_temp_dir(), 'subscriber-import-');
        file_put_contents(
            $this->importFile,
            "email,name\nCSV@EXAMPLE.COM,CSV Subscriber\ninvalid,Ignored\n",
        );

        $result = app(SubscriberService::class)->importSpreadsheet(
            new SubscriberImportData($this->importFile, 'csv'),
        );

        $this->assertSame(1, $result);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'csv@example.com',
            'name' => 'CSV Subscriber',
        ]);
        $this->assertDatabaseMissing('subscribers', ['email' => 'invalid']);
    }

    public function test_invalid_charset_falls_back_to_the_original_text(): void
    {
        $this->importFile = tempnam(sys_get_temp_dir(), 'subscriber-import-');
        file_put_contents($this->importFile, "fallback@example.com Fallback\n");

        $result = app(SubscriberService::class)->importTextFile(
            new SubscriberImportData($this->importFile, 'txt', [], 'invalid-charset'),
        );

        $this->assertSame(1, $result);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'fallback@example.com',
            'name' => 'Fallback',
        ]);
    }
}
