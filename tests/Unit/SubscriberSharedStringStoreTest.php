<?php

namespace Tests\Unit;

use App\Services\SubscriberSharedStringStore;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SubscriberSharedStringStoreTest extends TestCase
{
    private ?string $archivePath = null;

    protected function tearDown(): void
    {
        if ($this->archivePath !== null) {
            @unlink($this->archivePath);
        }

        parent::tearDown();
    }

    public function test_it_reads_plain_and_rich_shared_strings_from_disk_backed_storage(): void
    {
        $this->archivePath = tempnam(sys_get_temp_dir(), 'subscriber-import-');
        $archive = new ZipArchive;

        $this->assertTrue($archive->open($this->archivePath, ZipArchive::OVERWRITE) === true);
        $archive->addFromString('xl/sharedStrings.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="2" uniqueCount="200001">
    <si><t>plain@example.com</t></si>
    <si><r><t>Rich</t></r><r><t> Name</t></r></si>
</sst>
XML);
        $archive->close();

        $store = SubscriberSharedStringStore::create($this->archivePath);

        $this->assertSame('plain@example.com', $store->get(0));
        $this->assertSame('Rich Name', $store->get(1));
        $this->assertSame('', $store->get(2));
        $this->assertSame('', $store->get(-1));

        $store->close();
    }

    public function test_it_returns_an_empty_lookup_for_an_invalid_archive(): void
    {
        $store = SubscriberSharedStringStore::create('/missing/subscribers.xlsx');

        $this->assertSame('', $store->get(0));
    }
}
