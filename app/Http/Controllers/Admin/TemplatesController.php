<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Templates\DeleteRequest;
use App\Http\Requests\Admin\Templates\StoreRequest;
use App\Http\Requests\Admin\Templates\UpdateRequest;
use App\Models\Macros;
use App\Repositories\CategoryRepository;
use App\Repositories\TemplateRepository;
use App\Services\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;


class TemplatesController extends Controller
{
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TemplateService $templateService,
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.templates.index', [
            'categoryOptions' => $this->categoryRepository->getOption(),
            'infoAlert' => __('frontend.hint.template_index'),
            'title' => __('frontend.title.template_index'),
        ]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.templates.create_edit', [
            'infoAlert' => __('frontend.hint.template_create'),
            'macrosList' => $this->getMacros(),
            'title' => __('frontend.title.template_create'),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $template = $this->templateRepository->create($request->validated());

            $this->templateService->storeAttach($request, $template->id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.templates.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $template = $this->templateRepository->find($id);

        abort_if(!$template, 404);

        return view('admin.templates.create_edit', [
            'template' => $template,
            'attachment' => $template->attach,
            'infoAlert' => __('frontend.hint.template_edit'),
            'macrosList' => $this->getMacros(),
            'title' => __('frontend.title.template_edit'),
        ]);
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $this->templateRepository->updateWithMapping(
                (int) $request->id,
                $request->safe()->except(['id'])
            );

            $this->templateService->storeAttach($request, (int) $request->id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.templates.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param DeleteRequest $request
     * @return RedirectResponse
     */
    public function destroy(DeleteRequest $request): RedirectResponse
    {
        try {
            $this->templateRepository->remove((int) $request->id);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.templates.index')->with('success', __('message.data_deleted'));
    }

    /**
     * @param DeleteRequest $request
     * @return RedirectResponse
     */
    public function status(DeleteRequest $request): RedirectResponse
    {
        try {
            $this->templateRepository->updateStatus(
                $request->templateId,
                (int) $request->action
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.templates.index')->with('success', __('message.actions_completed'));
    }

    /**
     * @return string
     */
    private function getMacros(): string
    {
        return Macros::query()
            ->get()
            ->map(fn ($macro) => '{{' . $macro->name . '}} - ' . $macro->getType())
            ->implode(', ');
    }
}
