<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AuthController,
    CategoryController,
    DataTableController,
    TemplatesController,


};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.submit');
Route::get('logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [TemplatesController::class, 'index'])->name('admin.templates.index');


Route::group(['prefix' => 'category'], function () {
    Route::get('', [CategoryController::class, 'index'])->name('admin.category.index')->middleware(['permission:admin|moderator']);
    Route::get('create', [CategoryController::class, 'create'])->name('admin.category.create')->middleware(['permission:admin|moderator']);
    Route::post('store', [CategoryController::class, 'store'])->name('admin.category.store')->middleware(['permission:admin|moderator']);
    Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('admin.category.edit')->where('id', '[0-9]+')->middleware(['permission:admin|moderator']);
    Route::put('update', [CategoryController::class, 'update'])->name('admin.category.update')->middleware(['permission:admin|moderator']);
    Route::post('destroy', [CategoryController::class, 'destroy'])->name('admin.category.destroy')->middleware(['permission:admin|moderator']);
});

Route::group(['prefix' => 'datatable'], function () {
    Route::any('category', [DataTableController::class, 'getCategory'])->name('admin.datatable.category');
});




