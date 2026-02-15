<?php

namespace App\Http\Controllers\Admin;


use App\Repositories\CategoryRepository;
use App\Repositories\TemplateRepository;
use App\Models\Schedule;
use App\Repositories\ScheduleRepository;
use App\Http\Requests\Admin\Schedule\StoreRequest;
use App\Http\Requests\Admin\Schedule\EditRequest;
use App\Http\Requests\Admin\Schedule\DeleteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private CategoryRepository $categoryRepository,
        private TemplateRepository $templateRepository,
    )
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $schedule = Schedule::get();
        $infoAlert = __('frontend.hint.schedule_index') ?? null;

        return view('admin.schedule.index', compact('schedule', 'infoAlert'))->with('title', __('frontend.title.schedule_index'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        return response()->json($this->scheduleRepository->getScheduleByDateInterval($request));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        switch ($request->type) {
            case 'edit':
                $event = Schedule::find($request->id)->update([
                    'event_name' => $request->event_name,
                    'event_start' => $request->event_start,
                    'event_end' => $request->event_end,
                ]);

                return response()->json($event);

            case 'delete':
                $event = Schedule::find($request->id)->delete();

                return response()->json($event);
            default:
                break;
        }
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = $this->templateRepository->getOption();
        $category_options = $this->categoryRepository->getOption();
        $infoAlert = __('frontend.hint.schedule_create') ?? null;

        return view('admin.schedule.create_edit', compact('options', 'category_options', 'infoAlert'))->with('title', __('frontend.title.schedule_index'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->scheduleRepository->add($request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.schedule.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->scheduleRepository->find($id);

        if (!$row) abort(404);

        $categoryId = [];

        foreach ($row->categories ?? [] as $category) {
            $categoryId[] = $category->id;
        }

        $options = $this->templateRepository->getOption();
        $category_options = $this->categoryRepository->getOption();
        $date_interval = date("d.m.Y H:i", strtotime($row->event_start)) . ' - ' . date("d.m.Y H:i", strtotime($row->end_date));
        $infoAlert = __('frontend.hint.schedule_edit') ?? null;

        return view('admin.schedule.create_edit', compact('categoryId', 'options', 'category_options', 'row', 'infoAlert', 'date_interval'))->with('title', __('frontend.title.schedule_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->scheduleRepository->updateWithMapping($request->id, $request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.schedule.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param DeleteRequest $request
     * @return void
     */
    public function destroy(DeleteRequest $request): void
    {
        $this->scheduleRepository->delete($request->id);
    }
}
