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
    /**
     * Return email template rows formatted for the templates DataTable.
     *
     * @throws \Exception
     */
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
            ->addColumn('action', function ($row) {
                $showBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-info" href="%s"><span class="fa fa-eye"></span></a>&nbsp;',
                    __('frontend.str.template'),
                    route('admin.templates.show', ['id' => $row->id])
                );

                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.templates.edit', ['id' => $row->id])
                );

                return '<div class="nobr">'.$showBtn.$editBtn.'</div>';
            })
            ->editColumn('name', function ($row) {
                $body = preg_replace('/(<.*?>)|(&.*?;)/', '', $row->body);

                return $row->name.'<br><br><small class="text-muted">'.
                    StringHelper::shortText($body ?? '', 500).
                    '</small>';
            })
            ->editColumn('prior', fn ($row) => $row->getPrior())
            ->addColumn('attach', fn ($row) => $row->attach->count() > 0
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'name', 'checkbox'])
            ->make(true);
    }

    /**
     * Return category rows with subscriber counts and action buttons for DataTables.
     *
     * @throws \Exception
     */
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

                return '<div class="nobr">'.$editBtn.$deleteBtn.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Return SMTP account rows with status, checkbox, and action columns for DataTables.
     *
     * @throws \Exception
     */
    public function getSmtp(): JsonResponse
    {
        $rows = Smtp::query();

        return DataTables::of($rows)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="check" value="%d" name="activate[]">',
                $row->id
            ))
            ->editColumn('active', fn ($row) => $row->active
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('activeStatus', fn ($row) => (bool) $row->active)
            ->addColumn('action', function ($row) {
                $showBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-info" href="%s"><span class="fa fa-eye"></span></a>&nbsp;',
                    __('frontend.str.smtp_server'),
                    route('admin.smtp.show', ['id' => $row->id])
                );

                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.smtp.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<a class="btn btn-xs btn-danger deleteRow" id="%d"><span class="fa fa-trash"></span></a>',
                    $row->id
                );

                return '<div class="nobr">'.$showBtn.$editBtn.$deleteBtn.'</div>';
            })
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }

    /**
     * Return subscriber rows with category names, status, and action columns for DataTables.
     *
     * @throws \Exception
     */
    public function getSubscribers(): JsonResponse
    {
        $rows = Subscribers::query()
            ->with(['subscriptions:subscriber_id,category_id', 'subscriptions.category:id,name'])
            ->select([
                'subscribers.id',
                'subscribers.name',
                'subscribers.email',
                'subscribers.active',
                'subscribers.created_at',
            ]);

        return DataTables::of($rows)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="check" value="%d" name="activate[]">',
                $row->id
            ))
            ->addColumn('subscriptions', function ($row) {
                return $row->subscriptions
                    ->map(fn ($subscription) => $subscription->category?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            })
            ->editColumn('active', fn ($row) => $row->active === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('activeStatus', fn ($row) => $row->active)
            ->addColumn('action', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-primary" href="%s"><span class="fa fa-edit"></span></a>&nbsp;',
                    __('frontend.str.edit'),
                    route('admin.subscribers.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<a title="%s" class="btn btn-xs btn-danger deleteRow" id="%d"><span class="fa fa-trash"></span></a>',
                    __('frontend.str.remove'),
                    $row->id
                );

                return '<div class="nobr">'.$editBtn.$deleteBtn.'</div>';
            })
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }

    /**
     * Return admin user rows with role labels and action buttons for DataTables.
     *
     * @throws \Exception
     */
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

                return '<div class="nobr">'.$editBtn.$deleteBtn.'</div>';
            })
            ->editColumn('role', fn ($row) => $row->role_label)
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Return mailing summary rows for the log overview DataTable.
     *
     * @throws \Exception
     */
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
            ->addColumn('report', fn ($row) => PermissionsHelper::hasPermission('admin')
                ? sprintf(
                    '<a href="%s">%s</a>',
                    route('admin.log.report', ['id' => $row->id]),
                    __('frontend.str.download')
                )
                : '')
            ->editColumn('event_start', fn ($row) => $this->formatDateTime($row->event_start))
            ->editColumn('event_end', fn ($row) => $this->formatDateTime($row->event_end))
            ->rawColumns(['count', 'report'])
            ->make(true);
    }

    /**
     * Return per-recipient delivery log rows, optionally filtered by schedule ID.
     *
     * @throws \Exception
     */
    public function getInfoLog(?int $id = null): JsonResponse
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
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->make(true);
    }

    /**
     * Return grouped redirect tracking rows with report links for DataTables.
     *
     * @throws \Exception
     */
    public function getRedirectLogs(): JsonResponse
    {
        $rows = Redirect::query()
            ->selectRaw('url, COUNT(email) as count')
            ->groupBy('url')
            ->distinct();

        return DataTables::of($rows)
            ->editColumn('count', fn ($row) => sprintf(
                '<a href="%s">%s</a>',
                route('admin.redirect.info', ['url' => $this->encodeRouteBase64($row->url)]),
                $row->count
            ))
            ->addColumn('report', fn ($row) => PermissionsHelper::hasPermission('admin')
                ? sprintf(
                    '<a href="%s">%s</a>',
                    route('admin.redirect.report', ['url' => $this->encodeRouteBase64($row->url)]),
                    __('frontend.str.download')
                )
                : '')
            ->rawColumns(['count', 'report'])
            ->make(true);
    }

    /**
     * Return redirect tracking details for a single encoded URL.
     *
     * @throws \Exception
     */
    public function getInfoRedirectLog(string $url): JsonResponse
    {
        $decodedUrl = $this->decodeRouteBase64($url);

        $rows = Redirect::query()->where('url', $decodedUrl);

        return DataTables::of($rows)
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->make(true);
    }

    /**
     * Encode binary-safe base64 for a single route segment.
     */
    private function encodeRouteBase64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Decode URL-safe or regular base64 route parameters.
     */
    private function decodeRouteBase64(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($normalized, true) ?: '';
    }

    /**
     * Return macro rows with action buttons for the macros DataTable.
     *
     * @throws \Exception
     */
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

                return '<div class="nobr">'.$editBtn.$deleteBtn.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Normalize database or date-like values for display in DataTables.
     */
    private function formatDateTime(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp !== false
            ? date('Y-m-d H:i:s', $timestamp)
            : (string) $value;
    }
}
