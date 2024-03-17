<?php

namespace App\Http\Controllers\Admin;

class TemplatesController extends Controller
{
    public function index()
    {
        return view('admin.templates.index')->with('title', 'Главная');
    }
}
