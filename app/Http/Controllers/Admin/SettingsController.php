<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Settings\UpdateRequest;
use App\Models\Charsets;
use App\Models\Customheaders;
use App\Repositories\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'option_charset' => Charsets::getOption(),
            'customHeaders' => Customheaders::get(),
            'infoAlert' => __('frontend.hint.settings_index'),
            'title' => __('frontend.title.settings_index'),
        ]);
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $this->settingsRepository->setSettings(
                $request->validated()
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.settings.index')->with('success', __('message.data_updated'));
    }
}
