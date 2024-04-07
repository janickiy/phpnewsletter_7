<?php

namespace App\Http\Controllers\Admin;

use App\Models\{
    Category,
    Smtp,
    Subscribers,
    User,
};
use DataTables;
use Illuminate\Support\Facades\Auth;
use URL;

class DataTableController extends Controller
{
    /**
     * @return mixed
     */
    public function getCategory()
    {
        $row = Category::query();

        return Datatables::of($row)
            ->addColumn('actions', function ($row) {
                $editBtn = '<a title="редактировать" class="btn btn-xs btn-primary"  href="' . URL::route('admin.category.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a title="удалить" class="btn btn-xs btn-danger deleteRow" id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['actions'])->make(true);
    }

    /**
     * @return mixed
     */
    public function getSmtp()
    {
        $row = Smtp::query();

        return Datatables::of($row)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="check" value="' . $row->id . '" name="activate[]">';
            })
            ->editColumn('active', function ($row) {
                return $row->active == 1 ? trans('frontend.str.yes') : trans('frontend.str.no');
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<a title="' . trans('frontend.str.edit') . '" class="btn btn-xs btn-primary"  href="' . URL::route('admin.smtp.edit', ['id' => $row->id]) . '"><span  class="fa fa-edit"></span></a> &nbsp;';
                $deleteBtn = '<a class="btn btn-xs btn-danger deleteRow" id="' . $row->id . '"><span class="fa fa-trash"></span></a>';

                return '<div class="nobr"> ' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['action', 'checkbox'])->make(true);
    }

    /**
     * @return mixed
     */
    public function getSubscribers()
    {
        $row = Subscribers::selectRaw('subscribers.*')
            ->leftJoin('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->distinct();

        return Datatables::of($row)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="check" value="' . $row->id . '" name="activate[]">';
            })
            ->addColumn('categories', function ($row) {
                $categories = [];

                foreach ($row->subscriptions as $subscription) {
                    $categories[] = $subscription->category->name;
                }

                return implode(', ', $categories);
            })
            ->editColumn('active', function ($row) {
                return $row->active == 1 ? trans('frontend.str.yes') : trans('frontend.str.no');
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
     * @return mixed
     */
    public function getUsers()
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
                        $role = trans('frontend.str.admin');
                        break;
                    case 'editor':
                        $role = trans('frontend.str.editor');
                        break;
                    case 'moderator':
                        $role = trans('frontend.str.moderator');
                        break;
                    default:
                        $role = '';
                }

                return $role;
            })
            ->rawColumns(['action', 'id'])->make(true);
    }

}
