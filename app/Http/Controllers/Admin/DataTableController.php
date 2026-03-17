<?php

namespace App\Http\Controllers\Admin;


use App\Helpers\PermissionsHelper;
use App\Helpers\StringHelper;
use App\Models\Category;
use App\Models\Macros;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Schedule;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class DataTableController extends Controller
{
    public function getTemplates(): JsonResponse
    {
        $rows = Templates::query()
            ->with('attach')
            ->select('templates.*');

        return DataTables::of($rows)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="check" value="%d" name="templateId[]">',
                $row->id
            ))
            ->addColumn('action', fn ($row) => sprintf(
                '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                __('frontend.str.edit'),
                route('admin.templates.edit', ['id' => $row->id])
            ))
            ->editColumn('name', function ($row) {
                $body = preg_replace('/(<.*?>)|(&.*?;)/', '', $row->body);

                return $row->name . '<br><br><small class="text-muted">' .
                    StringHelper::shortText($body ?? '', 500) .
                    '</small>';
            })
            ->editColumn('prior', fn ($row) => $row->getPrior())
            ->addColumn('attach', fn ($row) => $row->attach->count() > 0
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->rawColumns(['action', 'name', 'checkbox'])
            ->make(true);
    }

    public function getCategory(): JsonResponse
    {
        $rows = Category::query()
            ->selectRaw('categories.id, categories.name, count(subscriptions.category_id) AS subcount')
            ->leftJoin('subscriptions', 'categories.id', '=', 'subscriptions.category_id')
            ->groupBy('categories.id', 'categories.name');

        return DataTables::of($rows)
            ->addColumn('actions', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.category.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-danger deleteRow" id="%d"><span class="fa fa-trash"></span></a>',
                    __('frontend.str.remove'),
                    $row->id
                );

                return '<div class="nobr">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getSmtp(): JsonResponse
    {
        $rows = Smtp::query();

        return DataTables::of($rows)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="check" value="%d" name="activate[]">',
                $row->id
            ))
            ->editColumn('active', fn ($row) => $row->active === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('activeStatus', fn ($row) => $row->active)
            ->addColumn('action', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.smtp.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<a class="btn btn-xs btn-danger deleteRow" id="%d"><span class="fa fa-trash"></span></a>',
                    $row->id
                );

                return '<div class="nobr">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }

    public function getSubscribers(): JsonResponse
    {
        $rows = Subscribers::query()
            ->with('subscriptions.category')
            ->select('subscribers.*', 'subscriptions.subscriber_id')
            ->leftJoin('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->distinct();

        return DataTables::of($rows)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="check" value="%d" name="activate[]">',
                $row->id
            ))
            ->addColumn('subscriptions', function ($row) {
                return $row->subscriptions
                    ->pluck('category.name')
                    ->filter()
                    ->implode(', ');
            })
            ->editColumn('active', fn ($row) => $row->active === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('activeStatus', fn ($row) => $row->active)
            ->addColumn('action', fn ($row) => sprintf(
                '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                __('frontend.str.edit'),
                route('admin.subscribers.edit', ['id' => $row->id])
            ))
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }

    public function getUsers(): JsonResponse
    {
        $rows = User::query();

        return DataTables::of($rows)
            ->addColumn('action', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.users.edit', ['id' => $row->id])
                );

                $deleteBtn = (int) $row->id !== (int) Auth::id()
                    ? sprintf(
                        '<a title="%s" class="btn btn-xs btn-danger deleteRow" id="%d"><span class="fa fa-trash"></span></a>',
                        __('frontend.str.remove'),
                        $row->id
                    )
                    : '';

                return '<div class="nobr">' . $editBtn . $deleteBtn . '</div>';
            })
            ->editColumn('role', fn ($row) => $row->role_label)
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getLogs(): JsonResponse
    {
        $rows = Schedule::query()
            ->selectRaw('schedule.id, schedule.event_start, schedule.event_end, COUNT(ready_sent.id) AS count, SUM(ready_sent.success=1) AS sent, SUM(ready_sent.readMail=1) AS read_mail')
            ->join('ready_sent', 'schedule.id', '=', 'ready_sent.schedule_id')
            ->groupBy('ready_sent.schedule_id', 'schedule.event_start', 'schedule.event_end', 'schedule.id');

        return DataTables::of($rows)
            ->editColumn('count', fn ($row) => sprintf(
                '<a href="%s">%s</a>',
                route('admin.log.info', ['id' => $row->id]),
                $row->count
            ))
            ->addColumn('unsent', fn ($row) => $row->count - $row->sent)
            ->editColumn('read_mail', fn ($row) => $row->read_mail ?? 0)
            ->addColumn('report', fn ($row) => PermissionsHelper::has_permission('admin')
                ? sprintf(
                    '<a href="%s">%s</a>',
                    route('admin.log.report', ['id' => $row->id]),
                    __('frontend.str.download')
                )
                : '')
            ->rawColumns(['count', 'report'])
            ->make(true);
    }

    public function getInfoLog(int $id = null): JsonResponse
    {
        $rows = $id
            ? ReadySent::query()->where('schedule_id', $id)
            : ReadySent::query();

        return DataTables::of($rows)
            ->editColumn('success', fn ($row) => $row->success === 1
                ? __('frontend.str.send_status_yes')
                : __('frontend.str.send_status_no'))
            ->editColumn('readMail', fn ($row) => $row->readMail === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->addColumn('status', fn ($row) => $row->success)
            ->addColumn('read', fn ($row) => $row->readMail)
            ->make(true);
    }

    public function getRedirectLogs(): JsonResponse
    {
        $rows = Redirect::query()
            ->selectRaw('url, COUNT(email) as count')
            ->groupBy('url')
            ->distinct();

        return DataTables::of($rows)
            ->editColumn('count', fn ($row) => sprintf(
                '<a href="%s">%s</a>',
                route('admin.redirect.info', ['url' => base64_encode($row->url)]),
                $row->count
            ))
            ->addColumn('report', fn ($row) => PermissionsHelper::has_permission('admin')
                ? sprintf(
                    '<a href="%s">%s</a>',
                    route('admin.redirect.report', ['url' => base64_encode($row->url)]),
                    __('frontend.str.download')
                )
                : '')
            ->rawColumns(['count', 'report'])
            ->make(true);
    }

    public function getInfoRedirectLog(string $url): JsonResponse
    {
        $decodedUrl = base64_decode($url, true) ?: '';

        $rows = Redirect::query()->where('url', $decodedUrl);

        return DataTables::of($rows)->make(true);
    }

    public function getMacros(): JsonResponse
    {
        $rows = Macros::query();

        return DataTables::of($rows)
            ->addColumn('actions', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.macros.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-danger deleteRow" id="%d"><span class="fa fa-trash"></span></a>',
                    __('frontend.str.remove'),
                    $row->id
                );

                return '<div class="nobr">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
