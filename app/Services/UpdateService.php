<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Helpers\UpdateHelper;
use App\Http\Traits\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UpdateService
{
    use File;

    private const DOWNLOAD_STEPS = [
        'download_update' => 'update.zip',
        'download_vendor' => 'vendor.zip',
        'download_public' => 'public.zip',
    ];

    private const EXTRACT_STEPS = [
        'extract_update' => 'update.zip',
        'extract_vendor' => 'vendor.zip',
        'extract_public' => 'public.zip',
    ];

    private const LEGACY_STEPS = [
        'start' => 'download_update',
        'upload_files_2' => 'download_vendor',
        'upload_files_3' => 'download_public',
        'update_files' => 'extract_update',
        'update_files_2' => 'extract_vendor',
        'update_files_3' => 'extract_public',
    ];

    private const DOWNLOAD_TIMEOUT = 300;
    private const DOWNLOAD_CONNECT_TIMEOUT = 20;

    /**
     * @param UpdateHelper $update
     * @param Request $request
     * @return array
     */
    public function startUpdate(UpdateHelper $update, Request $request): array
    {
        $step = $this->normalizeStep((string)$request->input('p'));

        if (isset(self::DOWNLOAD_STEPS[$step])) {
            return $this->downloadArchive($update, self::DOWNLOAD_STEPS[$step]);
        }

        if (isset(self::EXTRACT_STEPS[$step])) {
            return $this->extractArchive(self::EXTRACT_STEPS[$step]);
        }

        return match ($step) {
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

        return [
            'msg' => $message,
            'version' => $update->getVersion(),
        ];
    }

    /**
     * Return the client-side update queue in the required archive order.
     *
     * @return array<int, array<string, bool|int|string>>
     */
    public function getClientSteps(): array
    {
        return [
            [
                'p' => 'download_update',
                'status' => __('frontend.msg.downloading') . ' update.zip ...',
                'progress' => 15,
            ],
            [
                'p' => 'download_vendor',
                'status' => __('frontend.msg.downloading') . ' vendor.zip ...',
                'progress' => 30,
            ],
            [
                'p' => 'download_public',
                'status' => __('frontend.msg.downloading') . ' public.zip ...',
                'progress' => 40,
            ],
            [
                'p' => 'extract_update',
                'status' => __('frontend.msg.unzipping') . ' update.zip ...',
                'progress' => 55,
            ],
            [
                'p' => 'extract_vendor',
                'status' => __('frontend.msg.unzipping') . ' vendor.zip ...',
                'progress' => 70,
            ],
            [
                'p' => 'extract_public',
                'status' => __('frontend.msg.unzipping') . ' public.zip ...',
                'progress' => 80,
            ],
            [
                'p' => 'update_bd',
                'status' => __('frontend.msg.update_bd'),
                'progress' => 90,
            ],
            [
                'p' => 'clear_cache',
                'status' => __('frontend.msg.completing_update'),
                'progress' => 100,
                'final' => true,
            ],
        ];
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

        $url = rtrim($updateLink, '/') . '/' . rawurlencode($fileName);
        $downloadResult = $this->downloadRemoteArchive($url, $fileName);

        if ($downloadResult !== true) {
            return $this->makeResponse(
                false,
                $downloadResult
            );
        }

        return $this->makeResponse(
            true,
            __('frontend.msg.download_completed') . ': ' . $fileName
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

        if (!$zip->extractTo(base_path())) {
            $zip->close();

            return $this->makeResponse(
                false,
                __('frontend.msg.failed_to_update') . ': ' . $fileName
            );
        }

        $zip->close();

        return $this->makeResponse(
            true,
            __('frontend.msg.files_unzipped_successfully') . ': ' . $fileName
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

    private function normalizeStep(string $step): string
    {
        return self::LEGACY_STEPS[$step] ?? $step;
    }

    private function downloadRemoteArchive(string $url, string $fileName): bool|string
    {
        $disk = Storage::disk('public');
        $destination = $disk->path($fileName);
        $temporaryDestination = $destination . '.download';
        $directory = dirname($destination);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return __('frontend.msg.directory_not_writeable') . ': ' . $directory;
        }

        $handle = @fopen($temporaryDestination, 'wb');

        if ($handle === false) {
            return __('frontend.msg.directory_not_writeable') . ': ' . $fileName;
        }

        $curl = curl_init($url);

        if ($curl === false) {
            fclose($handle);
            @unlink($temporaryDestination);

            return __('frontend.msg.failed_to_update') . ': ' . $fileName;
        }

        $curlOptions = [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => self::DOWNLOAD_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::DOWNLOAD_TIMEOUT,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'PHPNewsletter updater',
        ];

        if (defined('CURLOPT_PROTOCOLS')) {
            $curlOptions[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $curlOptions[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        curl_setopt_array($curl, $curlOptions);

        $downloaded = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);

        curl_close($curl);
        fclose($handle);

        if ($downloaded !== true || $httpCode < 200 || $httpCode >= 300 || !is_file($temporaryDestination) || filesize($temporaryDestination) === 0) {
            @unlink($temporaryDestination);

            $error = $curlError !== '' ? $curlError : 'HTTP ' . $httpCode;

            return __('frontend.msg.failed_to_update') . ': ' . $fileName . ' (' . $error . ')';
        }

        $zip = new ZipArchive();
        $zipResult = $zip->open($temporaryDestination, ZipArchive::CHECKCONS);

        if ($zipResult !== true) {
            @unlink($temporaryDestination);

            return __('frontend.msg.cannot_read_zip_archive') . ': ' . $fileName;
        }

        $zip->close();

        if (!@rename($temporaryDestination, $destination)) {
            @unlink($temporaryDestination);

            return __('frontend.msg.directory_not_writeable') . ': ' . $fileName;
        }

        return true;
    }
}
