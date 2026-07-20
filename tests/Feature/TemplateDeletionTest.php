<?php

namespace Tests\Feature;

use App\Models\Attach;
use App\Models\Templates;
use App\Repositories\TemplateRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_removes_template_attachments_and_files(): void
    {
        Storage::fake();

        $template = Templates::query()->create([
            'name' => 'Template to delete',
            'body' => '<p>Body</p>',
            'prior' => 0,
        ]);
        $attachment = Attach::query()->create([
            'name' => 'Report',
            'file_name' => 'report.txt',
            'template_id' => $template->id,
        ]);
        $path = Attach::DIRECTORY.'/'.$attachment->file_name;
        Storage::put($path, 'attachment contents');

        $this->assertTrue(app(TemplateRepository::class)->remove($template->id));

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('attach', ['id' => $attachment->id]);
        Storage::assertMissing($path);
    }

    public function test_repository_returns_false_for_unknown_template(): void
    {
        $this->assertFalse(app(TemplateRepository::class)->remove(999));
    }
}
