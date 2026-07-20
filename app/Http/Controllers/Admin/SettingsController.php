<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SettingsHelper;
use App\Http\Requests\Admin\Settings\UpdateRequest;
use App\Models\Charsets;
use App\Models\CustomHeaders;
use App\Repositories\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Inject the settings repository used to persist application configuration.
     */
    public function __construct(
        private readonly SettingsRepository $settingsRepository
    ) {
        parent::__construct();
    }

    /**
     * Show the application settings page with charset and custom header options.
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'option_charset' => Charsets::getOption(),
            'customHeaders' => CustomHeaders::get(),
            'settings' => SettingsHelper::all(),
            'infoAlert' => __('frontend.hint.settings_index'),
            'title' => __('frontend.title.settings_index'),
        ]);
    }

    /**
     * Persist application settings submitted from the settings form.
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $this->settingsRepository->setSettings($request->validated());
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.settings.index')->with('success', __('message.data_updated'));
    }
}
