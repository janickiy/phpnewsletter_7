<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class UpdateController extends Controller
{
    public function index(): View
    {
        return view('admin.update.index')->with('title', trans('frontend.title.category_index'));
    }
}
