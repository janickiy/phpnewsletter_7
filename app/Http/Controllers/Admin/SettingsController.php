<?php

namespace App\Http\Controllers\Admin;


use App\Repositories\SettingsRepository;
use App\Models\Charsets;
use App\Models\Customheaders;
use App\Helpers\StringHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Exception;

class SettingsController extends Controller
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $option_charset = [];

        foreach (Charsets::orderBy('charset')->get() ?? [] as $row) {
            $option_charset[$row->charset] = StringHelper::charsetList($row->charset);
        }

        $customHeaders = Customheaders::get();
        $infoAlert = __('frontend.hint.settings_index') ?? null;

        return view('admin.settings.index', compact('option_charset', 'customHeaders', 'infoAlert'))->with('title',  __('frontend.title.settings_index'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            $this->settingsRepository->setSettings($request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.settings.index')->with('success', __('message.data_updated'));
    }
}
