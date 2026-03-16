<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Helpers\UpdateHelper;
use App\Http\Traits\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class UpdateService
{
    use File;

    /**
     * @param UpdateHelper $update
     * @param Request $request
     * @return array
     */
    public function startUpdate(UpdateHelper $update, Request $request): array
    {
        return match ($request->input('p')) {
            'start' => $this->downloadArchive($update, 'update.zip'),
            'upload_files_2' => $this->downloadArchive($update, 'public.zip'),
            'upload_files_3' => $this->downloadArchive($update, 'vendor.zip'),

            'update_files' => $this->extractArchive('update.zip'),
            'update_files_2' => $this->extractArchive('public.zip'),
            'update_files_3' => $this->extractArchive('vendor.zip'),

            'update_bd' => $this->updateDatabase(),
            'clear_cache' => $this->clearCache($update),

            default => [],
        };
    }

    /**
     * @param UpdateHelper $update
     * @return array|null[]
     */
    public function alertUpdate(UpdateHelper $update): array
    {
        if (!$update->checkNewVersion()) {
            return ['msg' => null];
        }

        $message = str_replace(
            ['%SCRIPTNAME%', '%VERSION%', '%CREATED%', '%DOWNLOADLINK%', '%MESSAGE%'],
            [
                __('frontend.str.script_name'),
                $update->getVersion(),
                $update->getCreated(),
                $update->getDownloadLink(),
                $update->getMessage(),
            ],
            __('frontend.str.update_warning')
        );

        return ['msg' => $message];
    }

    /**
     * @param UpdateHelper $update
     * @param string $fileName
     * @return array
     */
    private function downloadArchive(UpdateHelper $update, string $fileName): array
    {
        $updateLink = $update->getUpdateLink();

        if (!$updateLink) {
            return $this->makeResponse(
                false,
                __('frontend.msg.failed_to_update')
            );
        }

        $fileContent = @file_get_contents($updateLink . $fileName);

        if ($fileContent === false) {
            return $this->makeResponse(
                false,
                __('frontend.msg.failed_to_update')
            );
        }

        $downloadCompleted = self::download($fileName, $fileContent);

        return $this->makeResponse(
            $downloadCompleted === true,
            $downloadCompleted === true
                ? __('frontend.msg.download_completed')
                : __('frontend.msg.failed_to_update')
        );
    }

    /**
     * @param string $fileName
     * @return array
     */
    private function extractArchive(string $fileName): array
    {
        if (!self::isExist($fileName)) {
            return $this->makeResponse(
                false,
                __('frontend.msg.cannot_read_zip_archive')
            );
        }

        if (!is_writable(base_path())) {
            return $this->makeResponse(
                false,
                __('frontend.msg.directory_not_writeable')
            );
        }

        $zip = new ZipArchive();
        $result = $zip->open(self::get($fileName));

        if ($result !== true) {
            return $this->makeResponse(
                false,
                __('frontend.msg.cannot_read_zip_archive')
            );
        }

        $zip->extractTo(base_path());
        $zip->close();

        return $this->makeResponse(
            true,
            __('frontend.msg.files_unzipped_successfully')
        );
    }

    /**
     * @return array
     */
    private function updateDatabase(): array
    {
        Artisan::call('migrate', ['--force' => true]);

        return $this->makeResponse(
            true,
            __('frontend.msg.update_completed')
        );
    }

    private function clearCache(UpdateHelper $update): array
    {
        StringHelper::setEnvironmentValue('VERSION', $update->getUpgradeVersion());

        Artisan::call('cache:clear');
        Artisan::call('route:cache');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return $this->makeResponse(true, $update->getUpgradeVersion());
    }

    /**
     * @param bool $result
     * @param string $status
     * @return array
     */
    private function makeResponse(bool $result, string $status): array
    {
        return [
            'result' => $result,
            'status' => $status,
        ];
    }
}
