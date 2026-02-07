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
        switch ($request->input('p')) {
            case 'start':
                if ($update->getUpdateLink()) {
                    $download_completed = self::download('update.zip', file_get_contents($update->getUpdateLink() . 'update.zip'));
                } else {
                    $download_completed = false;
                }

                if ($download_completed === true) {
                    $content['status'] = __('frontend.msg.download_completed');
                    $content['result'] = true;
                } else {
                    $content['status'] = __('frontend.msg.failed_to_update');
                    $content['result'] = false;
                }

                return $content;

            case 'upload_files_2':
                if ($update->getUpdateLink()) {
                    $download_completed = self::download('public.zip', file_get_contents($update->getUpdateLink() . 'public.zip'));
                } else {
                    $download_completed = false;
                }

                if ($download_completed === true) {
                    $content['status'] = __('frontend.msg.download_completed');
                    $content['result'] = true;
                } else {
                    $content['status'] = __('frontend.msg.failed_to_update');
                    $content['result'] = false;
                }

                return $content;

            case 'upload_files_3':

                if ($update->getUpdateLink()) {
                    $download_completed = self::download('vendor.zip', file_get_contents($update->getUpdateLink() . 'vendor.zip'));
                } else {
                    $download_completed = false;
                }

                if ($download_completed === true) {
                    $content['status'] = __('frontend.msg.download_completed');
                    $content['result'] = true;
                } else {
                    $content['status'] = __('frontend.msg.failed_to_update');
                    $content['result'] = false;
                }

                return $content;

            case 'update_files':
                $zip = new ZipArchive();

                if (self::isExist('update.zip') && $zip->open(self::get('update.zip'))) {
                    if (is_writeable(base_path())) {
                        $zip->extractTo(base_path());
                        $content['status'] = __('frontend.msg.files_unzipped_successfully');
                        $content['result'] = true;
                        $zip->close();
                    } else {
                        $content['status'] = __('frontend.msg.directory_not_writeable');
                        $content['result'] = false;
                    }
                } else {
                    $content['status'] = __('frontend.msg.cannot_read_zip_archive');
                    $content['result'] = false;
                }

                return $content;


            case 'update_files_2':

                $zip = new ZipArchive();

                if (self::isExist('public.zip') && $zip->open(self::get('public.zip'))) {
                    if (is_writeable(base_path())) {
                        $zip->extractTo(base_path());
                        $content['status'] = __('frontend.msg.files_unzipped_successfully');
                        $content['result'] = true;
                        $zip->close();
                    } else {
                        $content['status'] = __('frontend.msg.directory_not_writeable');
                        $content['result'] = false;
                    }
                } else {
                    $content['status'] = __('frontend.msg.cannot_read_zip_archive');
                    $content['result'] = false;
                }

                return $content;

            case 'update_files_3':

                $zip = new ZipArchive();

                if (self::isExist('vendor.zip') && $zip->open(self::get('vendor.zip'))) {
                    if (is_writeable(base_path())) {
                        $zip->extractTo(base_path());
                        $content['status'] = __('frontend.msg.files_unzipped_successfully');
                        $content['result'] = true;
                        $zip->close();
                    } else {
                        $content['status'] = __('frontend.msg.directory_not_writeable');
                        $content['result'] = false;
                    }
                } else {
                    $content['status'] = __('frontend.msg.cannot_read_zip_archive');
                    $content['result'] = false;
                }

                return $content;

            case 'update_bd':

                Artisan::call('migrate', ['--force' => true]);
                $content['status'] = __('frontend.msg.update_completed');
                $content['result'] = true;

                return $content;

            case 'clear_cache':

                StringHelper::setEnvironmentValue('VERSION', $update->getUpgradeVersion());
                Artisan::call('cache:clear');
                Artisan::call('route:cache');
                Artisan::call('route:clear');
                Artisan::call('view:clear');

                $content['status'] = $update->getUpgradeVersion();
                $content['result'] = true;

                return $content;
            default:

                return [];
        }
    }

    /**
     * @param UpdateHelper $update
     * @return array|null[]
     */
    public function alertUpdate(UpdateHelper $update): array
    {
        if ($update->checkNewVersion()) {
            $update_warning = str_replace('%SCRIPTNAME%', __('frontend.str.script_name'), __('frontend.str.update_warning'));
            $update_warning = str_replace('%VERSION%', $update->getVersion(), $update_warning);
            $update_warning = str_replace('%CREATED%', $update->getCreated(), $update_warning);
            $update_warning = str_replace('%DOWNLOADLINK%', $update->getDownloadLink(), $update_warning);
            $update_warning = str_replace('%MESSAGE%', $update->getMessage(), $update_warning);

            return ["msg" => $update_warning];
        }

        return ["msg" => null];
    }
}
