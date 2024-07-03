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
            'start_date' => date("Y-m-d H:i:s", strtotime($date[0])),
            'end_date' => date("Y-m-d H:i:s", strtotime($date[1]))
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

        $schedule->start_date = date("Y-m-d H:i:s", strtotime($date[0]));
        $schedule->end_date = date("Y-m-d H:i:s", strtotime($date[1]));
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
