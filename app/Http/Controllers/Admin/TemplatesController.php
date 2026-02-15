<?php

namespace App\Http\Controllers\Admin;


use App\Repositories\CategoryRepository;
use App\Repositories\TemplateRepository;
use App\Services\TemplateService;
use App\Models\{Macros, Templates};
use App\Http\Requests\Admin\Templates\StoreRequest;
use App\Http\Requests\Admin\Templates\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;

class TemplatesController extends Controller
{
    public function __construct(
        private TemplateRepository $templateRepository,
        private CategoryRepository $categoryRepository,
        private TemplateService    $templateService,
    )
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $categoryOptions = $this->categoryRepository->getOption();

        $infoAlert = __('frontend.hint.template_index') ?? null;

        return view('admin.templates.index', compact('infoAlert', 'categoryOptions'))->with('title', __('frontend.title.template_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = __('frontend.hint.template_create') ?? null;

        $macrosList = $this->getMacros();

        return view('admin.templates.create_edit', compact('infoAlert', 'macrosList'))->with('title', __('frontend.title.template_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $template = $this->templateRepository->create($request->all());
            $this->templateService->addAttach($request, $template->id);
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.templates.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $template = $this->templateRepository->find($id);

        if (!$template) abort(404);

        $attachment = $template->attach;
        $infoAlert = __('frontend.hint.template_edit') ? __('frontend.hint.template_edit') : null;
        $macrosList = $this->getMacros();

        return view('admin.templates.create_edit', compact('template', 'attachment', 'infoAlert', 'macrosList'))->with('title', __('frontend.title.template_edit'));
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $this->templateRepository->updateWithMapping($request->id, $request->all());
            $this->templateService->updateAttach($request, $request->id);
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.templates.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        $this->templateRepository->remove($request->id);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $templateId = [];

        foreach ($request->templateId ?? [] as $id) {
            if (is_numeric($id)) {
                $templateId[] = $id;
            }
        }

        if ($request->action == 1) {
            $templates = Templates::whereIN('id', $templateId)->get();

            foreach ($templates ?? [] as $template) {
                $template->remove();
            }
        }

        return redirect()->route('admin.templates.index')->with('success', __('message.actions_completed'));
    }

    /**
     * @return string
     */
    private function getMacros(): string
    {
        $list = [];

        foreach (Macros::get() as $macro) {
            $list[] = '{{' . $macro->name . '}} - ' . $macro->getType();
        }

        return implode(', ', $list);
    }
}
