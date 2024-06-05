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

        $infoAlert = trans('frontend.hint.schedule_index') ? trans('frontend.hint.schedule_index') : null;

        return view('admin.schedule.index', compact('schedule', 'infoAlert'))->with('title', trans('frontend.title.schedule_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = Templates::getOption();
        $category_options = Category::getOption();

        $infoAlert = trans('frontend.hint.schedule_create') ? trans('frontend.hint.schedule_create') : null;

        return view('admin.schedule.create_edit', compact('options', 'category_options', 'infoAlert'))->with('title', trans('frontend.title.schedule_index'));

    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $id = Schedule::create(array_merge($request->all(), [
            'value_from_start_date' => date("Y-m-d H:i:s", strtotime($request->value_from_start_date)),
            'value_from_end_date' => date("Y-m-d H:i:s", strtotime($request->value_from_end_date))
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
        $schedule = Schedule::find($id);

        if (!$schedule) abort(404);

        $categoryId = [];

        foreach ($schedule->categories as $category) {
            $categoryId[] = $category->id;
        }

        $options = Templates::getOption();
        $category_options = Category::getOption();

        $infoAlert = trans('frontend.hint.schedule_edit') ? trans('frontend.hint.schedule_edit') : null;

        return view('admin.schedule.create_edit', compact('categoryId', 'options', 'category_options', 'schedule', 'infoAlert'))->with('title', trans('frontend.title.schedule_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $schedule = Schedule::find($request->id);

        if (!$schedule) abort(404);

        $schedule->value_from_start_date = date("Y-m-d H:i:s", strtotime($request->value_from_start_date));
        $schedule->value_from_end_date = date("Y-m-d H:i:s", strtotime($request->value_from_end_date));
        $schedule->templateId = $request->templateId;
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
