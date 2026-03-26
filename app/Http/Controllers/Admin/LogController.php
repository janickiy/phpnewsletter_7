<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logs;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LogController extends Controller
{
    /**
     * Display a listing of the logs.
     */
    public function index(): View
    {
        $rows = Logs::query()
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.log.index', [
            'rows' => $rows,
            'title' => __('message.logs'),
        ]);
    }

    /**
     * Clear all logs
     */
    public function clear(): RedirectResponse
    {
        try {
            // ВАЖНО: используем delete(), а не truncate()
            // чтобы не ломались foreign key (ready_sent → logs)
            Logs::query()->delete();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('message.data_deleted'));
    }
}
