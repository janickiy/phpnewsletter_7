<?php

namespace App\Http\Controllers\Admin;

use App\Models\{
    Schedule,
    Templates,
    Category,
    ScheduleCategory,
};
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\Schedule\StoreRequest;
use App\Http\Requests\Admin\Schedule\EditRequest;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $schedule = Schedule::get();
        $infoAlert = trans('frontend.hint.schedule_index') ?? null;

        return view('admin.schedule.index', compact('schedule', 'infoAlert'))->with('title', trans('frontend.title.schedule_index'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
       // if($request->ajax()) {
            $data = Schedule::whereDate('event_start', '>=', $request->start)
                ->whereDate('event_end',   '<=', $request->end)
                ->get(['id', 'event_name', 'event_start', 'event_end']);

            $items = [];

            foreach ($data as $row) {
                $items[] = [

                    'id' => $row->id,
                    'start' => $row->event_start, // Format as ISO 8601 with time zone
                    'end' => $row->event_end,
                    'title' => $row->event_name,

                ];
            }

            return response()->json( $items);
     //   }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        switch ($request->type) {
            case 'create':
                $event = Schedule::create([
                    'event_name' => $request->event_name,
                    'event_start' => $request->event_start,
                    'event_end' => $request->event_end,
                ]);

                return response()->json($event);

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
        $options = Templates::getOption();
        $category_options = Category::getOption();
        $infoAlert = trans('frontend.hint.schedule_create') ?? null;

        return view('admin.schedule.create_edit', compact('options', 'category_options', 'infoAlert'))->with('title', trans('frontend.title.schedule_index'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $date = explode(' - ', $request->date_interval);

        $id = Schedule::create(array_merge($request->all(), [
            'event_start' => date("Y-m-d H:i:s", strtotime($date[0])),
            'event_end' => date("Y-m-d H:i:s", strtotime($date[1])),
            'event_name' => $request->input('event_name'),
        ]))->id;

        if ($request->categoryId && $id) {
            foreach ($request->categoryId as $categoryId) {
                if (is_numeric($categoryId)) {
                    ScheduleCategory::create(['schedule_id' => $id, 'category_id' => $categoryId]);
                }
            }
        }

        return redirect()->route('admin.schedule.index')->with('success', trans('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Schedule::find($id);

        if (!$row) abort(404);

        $categoryId = [];

        foreach ($row->categories as $category) {
            $categoryId[] = $category->id;
        }

        $options = Templates::getOption();
        $category_options = Category::getOption();
        $date_interval = date("d.m.Y H:i", strtotime($row->start_date)) . ' - ' . date("d.m.Y H:i", strtotime($row->end_date));
        $infoAlert = trans('frontend.hint.schedule_edit') ?? null;

        return view('admin.schedule.create_edit', compact('categoryId', 'options', 'category_options', 'row', 'infoAlert', 'date_interval'))->with('title', trans('frontend.title.schedule_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $schedule = Schedule::find($request->id);

        if (!$schedule) abort(404);

        $date = explode(' - ', $request->date_interval);

        $schedule->event_name = $request->input('event_name');
        $schedule->event_start = date("Y-m-d H:i:s", strtotime($date[0]));
        $schedule->event_end = date("Y-m-d H:i:s", strtotime($date[1]));
        $schedule->template_id = $request->input('template_id');
        $schedule->save();

        ScheduleCategory::where('schedule_id', $request->id)->delete();

        if ($request->categoryId) {
            foreach ($request->categoryId as $categoryId) {
                if (is_numeric($categoryId)) {
                    ScheduleCategory::create(['schedule_id' => $request->id, 'category_id' => $categoryId]);
                }
            }
        }

        return redirect()->route('admin.schedule.index')->with('success', trans('message.data_updated'));
    }

    /**
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        Schedule::find($id)->delete();
        ScheduleCategory::where('schedule_id', $id)->delete();
    }
}
