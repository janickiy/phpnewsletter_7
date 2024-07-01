<?php

namespace App\Http\Controllers\Admin;

use App\Models\{Category, Smtp, Subscribers, Schedule, Templates, User, ReadySent, Redirect};
use App\Helpers\{StringHelper, PermissionsHelper};
use Illuminate\Support\Facades\Auth;
use DataTables;
use Symfony\Component\HttpFoundation\JsonResponse;
use URL;

class DataTableController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getTemplates(): JsonResponse
    {
        $row = Templates::with('attach')->select('templates.*');

        return Datatables::of($row)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="check" value="' . $row->id . '" name="templateId[]">';
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<a title="' . trans('frontend.str.edit') . '" class="btn btn-xs btn-primary"  href="' . URL::route('admin.templates.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                return $editBtn;

            })
            ->editColumn('name', function ($row) {
                $body = preg_replace('/(<.*?>)|(&.*?;)/', '', $row->body);
                return $row->name . '<br><br><small class="text-muted">' . StringHelper::shortText($body, 500) . '</small>';
            })
            ->editColumn('prior', function ($row) {
                return Templates::getPrior($row->id);
            })
            ->addColumn('attach', function ($row) {
                return count($row->attach) > 0 ? trans('frontend.str.yes') : trans('frontend.str.no');
            })
            ->rawColumns(['action', 'name', 'checkbox'])->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function getCategory(): JsonResponse
    {
        $row = Category::query()
            ->selectRaw('categories.id,categories.name,count(subscriptions.category_id) AS subcount')
            ->leftJoin('subscriptions', 'categories.id', '=', 'subscriptions.category_id')
            ->groupBy('categories.id')
            ->groupBy('categories.name');

        return Datatables::of($row)
            ->addColumn('actions', function ($row) {
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary"  href="' . URL::route('admin.category.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['actions'])->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function getSmtp(): JsonResponse
    {
        $row = Smtp::query();

        return Datatables::of($row)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="check" value="' . $row->id . '" name="activate[]">';
            })
            ->editColumn('active', function ($row) {
                return $row->active === 1 ? trans('frontend.str.yes') : trans('frontend.str.no');
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<a title="' . trans('frontend.str.edit') . '" class="btn btn-xs btn-primary"  href="' . URL::route('admin.smtp.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a class="btn btn-xs btn-danger deleteRow" id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action', 'checkbox'])->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function getSubscribers(): JsonResponse
    {
        $row = Subscribers::select('subscribers.*','subscriptions.subscriber_id')
            ->leftJoin('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->distinct();

        return Datatables::of($row)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="check" value="' . $row->id . '" name="activate[]">';
            })
            ->addColumn('subscriptions', function ($row) {
                $categories = [];

                foreach ($row->subscriptions as $subscription) {
                    $categories[] = $subscription->category->name;
                }

                return implode(', ', $categories);
            })
            ->editColumn('active', function ($row) {
                return $row->active === 1 ? trans('frontend.str.yes') : trans('frontend.str.no');
            })
            ->editColumn('activeStatus', function ($row) {
                return $row->active;
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<a title="' . trans('frontend.str.edit') . '" class="btn btn-xs btn-primary"  href="' . URL::route('admin.subscribers.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                return $editBtn;
            })
            ->rawColumns(['action', 'checkbox'])->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function getUsers(): JsonResponse
    {
        $row = User::query();

        return Datatables::of($row)
            ->addColumn('action', function ($row) {
                $editBtn = '<a title="' . trans('frontend.str.edit') . '" class="btn btn-xs btn-primary"  href="' . URL::route('admin.users.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';

                if ($row->id != Auth::id())
                    $deleteBtn = '<a class="btn btn-xs btn-danger deleteRow" id="' . $row->id . '"><span class="fa fa-remove"></span></a>';
                else
                    $deleteBtn = '';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->editColumn('role', function ($row) {
                switch ($row->role) {
                    case 'admin':
                        return trans('frontend.str.admin');
                    case 'editor':
                        return trans('frontend.str.editor');
                    case 'moderator':
                        return trans('frontend.str.moderator');
                    default:
                        return '';
                }
            })
            ->rawColumns(['action', 'id'])->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function getLogs(): JsonResponse
    {
        $row = Schedule::selectRaw('schedule.id, schedule.value_from_start_date, schedule.value_from_end_date, COUNT(ready_sent.id) AS count, SUM(ready_sent.success=1) AS sent, SUM(ready_sent.readMail=1) AS read_mail')
            ->join('ready_sent', 'schedule.id', '=', 'ready_sent.schedule_id')
            ->groupBy('ready_sent.schedule_id')
            ->groupBy('schedule.value_from_start_date')
            ->groupBy('schedule.value_from_end_date')
            ->groupBy('schedule.id');

        return Datatables::of($row)
            ->editColumn('count', function ($row) {
                return '<a href="' . URL::route('admin.log.info', ['id' => $row->id]) . '">' . $row->count . '</a>';
            })
            ->addColumn('unsent', function ($row) {
                return $row->count - $row->sent;
            })
            ->editColumn('read_mail', function ($row) {
                return $row->read_mail ?? 0;
            })
            ->addColumn('report', function ($row) {
                return PermissionsHelper::has_permission(Auth::user()->role, 'admin') ? '<a href="' . URL::route('admin.log.report', ['id' => $row->id]) . '">' . trans('frontend.str.download') . '</a>' : '';
            })
            ->rawColumns(['count', 'report'])->make(true);
    }

    /**
     * @param int|null $id
     * @return JsonResponse
     */
    public function getInfoLog(int $id = null): JsonResponse
    {
        $row = $id ? ReadySent::where('schedule_id', $id) : ReadySent::query();

        return Datatables::of($row)
            ->editColumn('success', function ($row) {
                return $row->success === 1 ? trans('frontend.str.send_status_yes') : trans('frontend.str.send_status_no');
            })
            ->editColumn('readMail', function ($row) {
                return $row->readMail === 1 ? trans('frontend.str.yes') : trans('frontend.str.no');
            })
            ->addColumn('status', function ($row) {
                return $row->success;
            })
            ->addColumn('read', function ($row) {
                return $row->readMail;
            })
            ->make(true);
    }

    /**
     * @return JsonResponse
     */
    public function getRedirectLogs(): JsonResponse
    {
        $row = Redirect::query()
            ->selectRaw('url,COUNT(email) as count')
            ->groupBy('url')
            ->distinct();

        return Datatables::of($row)
            ->editColumn('count', function ($row) {
                return '<a href="' . URL::route('admin.redirect.info', ['url' => base64_encode($row->url)]) . '">' . $row->count . '</a>';
            })
            ->addColumn('report', function ($row) {
                return PermissionsHelper::has_permission(Auth::user()->role, 'admin') ? '<a href="' . URL::route('admin.redirect.report', ['url' => base64_encode($row->url)]) . '">' . trans('frontend.str.download') . '</a>' : '';
            })
            ->rawColumns(['count', 'report'])->make(true);
    }

    /**
     * @param string $url
     * @return JsonResponse
     */
    public function getInfoRedirectLog(string $url): JsonResponse
    {
        $url = base64_decode($url);

        $row = Redirect::query()->where('url', $url);

        return Datatables::of($row)
            ->make(true);
    }
}
