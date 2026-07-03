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

    private const DOWNLOAD_CHUNK_SIZE = 524288;
    private const DOWNLOAD_TIMEOUT = 45;
    private const DOWNLOAD_CONNECT_TIMEOUT = 15;
    private const DOWNLOAD_CHUNK_ATTEMPTS = 3;
    private const REMOTE_SIZE_TIMEOUT = 20;

    /**
     * @param UpdateHelper $update
     * @param Request $request
     * @return array
     */
    public function startUpdate(UpdateHelper $update, Request $request): array
    {
        $step = $this->normalizeStep((string)$request->input('p'));

        if ($request->boolean('reset') && $step === 'download_update') {
            $this->clearDownloadedArchives();
        }

        if (isset(self::DOWNLOAD_STEPS[$step])) {
            return $this->downloadArchive($update, self::DOWNLOAD_STEPS[$step], $request->boolean('chunked'));
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
    private function downloadArchive(UpdateHelper $update, string $fileName, bool $chunkedClient): array
    {
        $updateLink = $update->getUpdateLink();

        if (!$updateLink) {
            return $this->makeResponse(
                false,
                __('frontend.msg.failed_to_update')
            );
        }

        $url = rtrim($updateLink, '/') . '/' . rawurlencode($fileName);
        return $this->downloadRemoteArchive($url, $fileName, $chunkedClient);
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

        $extractResult = $this->extractZipArchive($zip, base_path());
        $zip->close();

        if ($extractResult !== true) {
            return $this->makeResponse(false, $extractResult);
        }

        if ($fileName === 'public.zip') {
            $this->ensurePublicStorageLink();
        }

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
    private function makeResponse(bool $result, string $status, array $extra = []): array
    {
        return array_merge([
            'result' => $result,
            'status' => $status,
        ], $extra);
    }

    private function normalizeStep(string $step): string
    {
        return self::LEGACY_STEPS[$step] ?? $step;
    }

    private function clearDownloadedArchives(): void
    {
        $disk = Storage::disk('public');

        foreach (array_merge(self::DOWNLOAD_STEPS, self::EXTRACT_STEPS) as $fileName) {
            $disk->delete($fileName);
            $disk->delete($fileName . '.download');
            $disk->delete($fileName . '.meta');
        }
    }

    private function extractZipArchive(ZipArchive $zip, string $destination): bool|string
    {
        $destination = rtrim($destination, DIRECTORY_SEPARATOR);

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if ($entryName === false) {
                return __('frontend.msg.cannot_read_zip_archive');
            }

            $entryName = str_replace('\\', '/', $entryName);

            if ($this->shouldSkipZipEntry($entryName)) {
                continue;
            }

            $targetPath = $this->getZipEntryTargetPath($destination, $entryName);

            if ($targetPath === null) {
                continue;
            }

            if (str_ends_with($entryName, '/')) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    return __('frontend.msg.directory_not_writeable') . ': ' . $targetPath;
                }

                continue;
            }

            $targetDirectory = dirname($targetPath);

            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                return __('frontend.msg.directory_not_writeable') . ': ' . $targetDirectory;
            }

            if (is_link($targetPath)) {
                @unlink($targetPath);
            }

            $source = $zip->getStream($entryName);

            if ($source === false) {
                return __('frontend.msg.cannot_read_zip_archive') . ': ' . $entryName;
            }

            $target = @fopen($targetPath, 'wb');

            if ($target === false) {
                fclose($source);

                return __('frontend.msg.directory_not_writeable') . ': ' . $targetPath;
            }

            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);
        }

        return true;
    }

    private function shouldSkipZipEntry(string $entryName): bool
    {
        $entryName = ltrim($entryName, '/');

        return $entryName === ''
            || str_starts_with($entryName, '__MACOSX/')
            || str_ends_with($entryName, '/.DS_Store')
            || basename($entryName) === '.DS_Store'
            || $entryName === 'public/storage'
            || str_starts_with($entryName, 'public/storage/');
    }

    private function getZipEntryTargetPath(string $destination, string $entryName): ?string
    {
        $entryName = ltrim($entryName, '/');

        if (str_contains($entryName, '../') || str_starts_with($entryName, '..')) {
            return null;
        }

        return $destination . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entryName);
    }

    private function ensurePublicStorageLink(): void
    {
        $storageDirectory = storage_path('app/public');
        $publicStorage = public_path('storage');

        if (!is_dir($storageDirectory)) {
            @mkdir($storageDirectory, 0775, true);
        }

        if (is_link($publicStorage)) {
            $target = readlink($publicStorage);

            if ($target === $storageDirectory || $target === '../storage/app/public') {
                return;
            }

            @unlink($publicStorage);
        }

        if (file_exists($publicStorage)) {
            return;
        }

        Artisan::call('storage:link');
    }

    private function downloadRemoteArchive(string $url, string $fileName, bool $chunkedClient): array
    {
        $disk = Storage::disk('public');
        $destination = $disk->path($fileName);
        $temporaryDestination = $destination . '.download';
        $metadataDestination = $destination . '.meta';
        $directory = dirname($destination);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return $this->makeResponse(false, __('frontend.msg.directory_not_writeable') . ': ' . $directory);
        }

        $remoteSize = $this->readDownloadMetadata($metadataDestination, $url);

        if ($remoteSize === null) {
            if (is_file($temporaryDestination)) {
                @unlink($temporaryDestination);
            }

            $remoteSize = $this->getRemoteArchiveSize($url);

            if (!is_int($remoteSize)) {
                return $this->makeResponse(false, $remoteSize);
            }

            if (@file_put_contents($metadataDestination, json_encode([
                    'url' => $url,
                    'size' => $remoteSize,
                ])) === false) {
                return $this->makeResponse(false, __('frontend.msg.directory_not_writeable') . ': ' . $fileName . '.meta');
            }
        }

        clearstatcache(true, $temporaryDestination);
        $offset = is_file($temporaryDestination) ? (int)filesize($temporaryDestination) : 0;

        if ($offset < 0 || $offset > $remoteSize) {
            @unlink($temporaryDestination);
            @unlink($metadataDestination);
            $offset = 0;
        }

        if ($offset === $remoteSize && $remoteSize > 0) {
            return $this->finalizeDownloadedArchive($temporaryDestination, $destination, $metadataDestination, $fileName, $remoteSize);
        }

        $rangeEnd = min($offset + self::DOWNLOAD_CHUNK_SIZE - 1, $remoteSize - 1);
        $chunkResult = ['ok' => false, 'retryable' => false, 'error' => __('frontend.msg.failed_to_update')];

        for ($attempt = 1; $attempt <= self::DOWNLOAD_CHUNK_ATTEMPTS; $attempt++) {
            $chunkResult = $this->downloadRemoteArchiveRange($url, $temporaryDestination, $offset, $rangeEnd, $remoteSize);

            if ($chunkResult['ok'] === true || $chunkResult['retryable'] !== true) {
                break;
            }

            usleep(250000 * $attempt);
        }

        clearstatcache(true, $temporaryDestination);
        $downloadedSize = is_file($temporaryDestination) ? (int)filesize($temporaryDestination) : 0;

        if ($chunkResult['ok'] !== true) {
            if ($chunkResult['retryable'] === true) {
                return $this->makeDownloadProgressResponse(
                    $fileName,
                    $downloadedSize,
                    $remoteSize,
                    true,
                    $chunkResult['error']
                );
            }

            return $this->makeResponse(false, $chunkResult['error']);
        }

        if ($downloadedSize < $remoteSize) {
            if (!$chunkedClient) {
                return $this->makeResponse(
                    false,
                    __('frontend.msg.failed_to_update') . ': обновите страницу обновления и запустите снова'
                );
            }

            return $this->makeDownloadProgressResponse($fileName, $downloadedSize, $remoteSize);
        }

        return $this->finalizeDownloadedArchive($temporaryDestination, $destination, $metadataDestination, $fileName, $remoteSize);
    }

    private function makeDownloadProgressResponse(
        string $fileName,
        int $downloadedSize,
        int $remoteSize,
        bool $retry = false,
        string $retryError = ''
    ): array {
        $fileProgress = $remoteSize > 0 ? (int)floor(($downloadedSize / $remoteSize) * 100) : 0;
        $status = __('frontend.msg.downloading') . ' ' . $fileName . ' ... ' . $fileProgress . '%';

        if ($retry) {
            $status .= ' (' . __('frontend.msg.failed_to_update') . ', retry)';
        }

        return $this->makeResponse(
            true,
            $status,
            [
                'done' => false,
                'retry' => $retry,
                'retry_after' => $retry ? 2000 : 250,
                'retry_error' => $retryError,
                'file' => $fileName,
                'bytes_downloaded' => $downloadedSize,
                'bytes_total' => $remoteSize,
                'file_progress' => $fileProgress,
            ]
        );
    }

    private function readDownloadMetadata(string $metadataDestination, string $url): ?int
    {
        if (!is_file($metadataDestination)) {
            return null;
        }

        $metadata = json_decode((string)file_get_contents($metadataDestination), true);

        if (!is_array($metadata) || ($metadata['url'] ?? null) !== $url) {
            return null;
        }

        $size = $metadata['size'] ?? null;

        return is_int($size) && $size > 0 ? $size : null;
    }

    private function getRemoteArchiveSize(string $url): int|string
    {
        $curl = curl_init($url);

        if ($curl === false) {
            return __('frontend.msg.failed_to_update');
        }

        $curlOptions = [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => self::DOWNLOAD_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::REMOTE_SIZE_TIMEOUT,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'PHPNewsletter updater',
        ];

        $this->restrictCurlProtocols($curlOptions);
        curl_setopt_array($curl, $curlOptions);

        $received = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $contentLength = $this->getCurlContentLength($curl);
        $curlError = curl_error($curl);

        curl_close($curl);

        if ($received !== true || $httpCode < 200 || $httpCode >= 300 || $contentLength <= 0) {
            $error = $curlError !== '' ? $curlError : 'HTTP ' . $httpCode;

            return __('frontend.msg.failed_to_update') . ' (' . $error . ')';
        }

        return $contentLength;
    }

    private function downloadRemoteArchiveRange(
        string $url,
        string $temporaryDestination,
        int $offset,
        int $rangeEnd,
        int $remoteSize
    ): array {
        $handle = @fopen($temporaryDestination, $offset > 0 ? 'ab' : 'wb');

        if ($handle === false) {
            return $this->makeDownloadChunkResult(false, false, __('frontend.msg.directory_not_writeable') . ': ' . basename($temporaryDestination, '.download'));
        }

        $curl = curl_init($url);

        if ($curl === false) {
            fclose($handle);

            return $this->makeDownloadChunkResult(false, true, __('frontend.msg.failed_to_update') . ': ' . basename($temporaryDestination, '.download'));
        }

        $curlOptions = [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => self::DOWNLOAD_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::DOWNLOAD_TIMEOUT,
            CURLOPT_FAILONERROR => false,
            CURLOPT_RANGE => $offset . '-' . $rangeEnd,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'PHPNewsletter updater',
        ];

        $this->restrictCurlProtocols($curlOptions);
        curl_setopt_array($curl, $curlOptions);

        $downloaded = curl_exec($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlErrorNumber = curl_errno($curl);
        $curlError = curl_error($curl);

        curl_close($curl);
        fclose($handle);

        $rangeCompleted = $downloaded === true
            && ($httpCode === 206 || ($httpCode === 200 && $offset === 0 && $rangeEnd === $remoteSize - 1));

        clearstatcache(true, $temporaryDestination);
        $expectedSize = $rangeEnd + 1;
        $actualSize = is_file($temporaryDestination) ? (int)filesize($temporaryDestination) : 0;

        if (!$rangeCompleted && $httpCode >= 200 && $httpCode < 300 && $actualSize > $offset && $actualSize <= $expectedSize) {
            return $this->makeDownloadChunkResult(true);
        }

        if (!$rangeCompleted || $actualSize !== $expectedSize) {
            if ($offset === 0) {
                @unlink($temporaryDestination);
            }

            $error = $curlError !== '' ? $curlError : 'HTTP ' . $httpCode;

            return $this->makeDownloadChunkResult(
                false,
                $this->isRetryableDownloadFailure($curlErrorNumber, $curlError, $httpCode),
                __('frontend.msg.failed_to_update') . ': ' . basename($temporaryDestination, '.download') . ' (' . $error . ')'
            );
        }

        return $this->makeDownloadChunkResult(true);
    }

    private function makeDownloadChunkResult(bool $ok, bool $retryable = false, string $error = ''): array
    {
        return [
            'ok' => $ok,
            'retryable' => $retryable,
            'error' => $error,
        ];
    }

    private function isRetryableDownloadFailure(int $curlErrorNumber, string $curlError, int $httpCode): bool
    {
        if (in_array($curlErrorNumber, [
            CURLE_COULDNT_CONNECT,
            CURLE_RECV_ERROR,
            CURLE_GOT_NOTHING,
            CURLE_SSL_CONNECT_ERROR,
        ], true)) {
            return true;
        }

        if ($curlErrorNumber === CURLE_OPERATION_TIMEDOUT) {
            return str_contains($curlError, '0 out of 0 bytes received')
                || str_contains($curlError, 'SSL connection timeout');
        }

        return $httpCode === 0 || $httpCode >= 500;
    }

    private function finalizeDownloadedArchive(
        string $temporaryDestination,
        string $destination,
        string $metadataDestination,
        string $fileName,
        int $remoteSize
    ): array {
        if (!is_file($temporaryDestination) || filesize($temporaryDestination) !== $remoteSize) {
            return $this->makeResponse(false, __('frontend.msg.failed_to_update') . ': ' . $fileName);
        }

        $zip = new ZipArchive();
        $zipResult = $zip->open($temporaryDestination, ZipArchive::CHECKCONS);

        if ($zipResult !== true) {
            @unlink($temporaryDestination);
            @unlink($metadataDestination);

            return $this->makeResponse(false, __('frontend.msg.cannot_read_zip_archive') . ': ' . $fileName);
        }

        $zip->close();

        if (is_file($destination)) {
            @unlink($destination);
        }

        if (!@rename($temporaryDestination, $destination)) {
            @unlink($temporaryDestination);

            return $this->makeResponse(false, __('frontend.msg.directory_not_writeable') . ': ' . $fileName);
        }

        @unlink($metadataDestination);

        return $this->makeResponse(
            true,
            __('frontend.msg.download_completed') . ': ' . $fileName,
            [
                'done' => true,
                'file' => $fileName,
                'bytes_downloaded' => $remoteSize,
                'bytes_total' => $remoteSize,
                'file_progress' => 100,
            ]
        );
    }

    /**
     * @param array<int, mixed> $curlOptions
     */
    private function restrictCurlProtocols(array &$curlOptions): void
    {
        if (defined('CURLOPT_PROTOCOLS')) {
            $curlOptions[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $curlOptions[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
    }

    private function getCurlContentLength(\CurlHandle $curl): int
    {
        if (defined('CURLINFO_CONTENT_LENGTH_DOWNLOAD_T')) {
            return (int)curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD_T);
        }

        return (int)curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    }
}
