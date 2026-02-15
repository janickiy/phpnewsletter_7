<?php

namespace App\Services;


use App\DTO\AttachCreateData;
use App\Helpers\StringHelper;
use App\Models\Attach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

class TemplateService
{
    /**
     * @param Request $request
     * @param int $templateId
     * @return void
     * @throws Exception
     */
    public function addAttach(Request $request, int $templateId): void
    {
        $attachFile = $request->file('attachfile');

        foreach ($attachFile ?? [] as $file) {
            $filename = StringHelper::randomText(10) . '.' . $file->getClientOriginalExtension();

            if (Storage::putFileAs(Attach::DIRECTORY, $file, $filename) === false) {
                throw new Exception(sprintf("Couldn't save %s!", $file->getClientOriginalName()));
            }

            Attach::create(new AttachCreateData(
                name: $file->getClientOriginalName(),
                file_name: $filename,
                template_id: $templateId,
            ));
        }
    }

    /**
     * @param Request $request
     * @param int $templateId
     * @return void
     * @throws Exception
     */
    public function updateAttach(Request $request, int $templateId): void
    {
        Attach::query()->where('template_id', $templateId)->remove();

        $attachFile = $request->file('attachfile');

        foreach ($attachFile ?? [] as $file) {
            $filename = StringHelper::randomText(10) . '.' . $file->getClientOriginalExtension();

            if (Storage::putFileAs(Attach::DIRECTORY, $file, $filename) === false) {
                throw new Exception(sprintf("Couldn't save %s!", $file->getClientOriginalName()));
            }

            Attach::create(new AttachCreateData(
                name: $file->getClientOriginalName(),
                file_name: $filename,
                template_id: $templateId,
            ));
        }
    }
}
