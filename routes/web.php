<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AuthController,
    CategoryController,
    DataTableController,
    TemplatesController,
    SmtpController,
    SettingsController,
    SubscribersController,
    UsersController,
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


Route::middleware(['permission:admin|moderator'])->group(function () {
    Route::group(['prefix' => 'category'], function () {
        Route::get('', [CategoryController::class, 'index'])->name('admin.category.index');
        Route::get('create', [CategoryController::class, 'create'])->name('admin.category.create');
        Route::post('store', [CategoryController::class, 'store'])->name('admin.category.store');
        Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('admin.category.edit')->where('id', '[0-9]+');
        Route::put('update', [CategoryController::class, 'update'])->name('admin.category.update');
        Route::post('destroy', [CategoryController::class, 'destroy'])->name('admin.category.destroy');
    });
});


Route::middleware(['permission:admin'])->group(function () {
    Route::group(['prefix' => 'smtp'], function () {
        Route::get('', [SmtpController::class, 'index'])->name('admin.smtp.index');
        Route::get('create', [SmtpController::class, 'create'])->name('admin.smtp.create');
        Route::post('store', [SmtpController::class, 'store'])->name('admin.smtp.store');
        Route::get('edit/{id}', [SmtpController::class, 'edit'])->name('admin.smtp.edit')->where('id', '[0-9]+');
        Route::put('update', [SmtpController::class, 'update'])->name('admin.smtp.update');
        Route::delete('destroy', [SmtpController::class, 'destroy'])->name('admin.smtp.destroy');
        Route::post('status', [SmtpController::class, 'status'])->name('admin.smtp.status');
    });
});

Route::middleware(['permission:admin'])->group(function () {
    Route::group(['prefix' => 'settings'], function () {
        Route::get('', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('update', [SettingsController::class, 'update'])->name('admin.settings.update');
    });
});

Route::middleware(['permission:admin|moderator'])->group(function () {
    Route::group(['prefix' => 'subscribers'], function () {
        Route::get('', [SubscribersController::class, 'index'])->name('admin.subscribers.index');
        Route::get('create', [SubscribersController::class, 'create'])->name('admin.subscribers.create');
        Route::post('store', [SubscribersController::class, 'store'])->name('admin.subscribers.store');
        Route::get('edit/{id}', [SubscribersController::class, 'edit'])->name('admin.subscribers.edit')->where('id', '[0-9]+');
        Route::put('update', [SubscribersController::class, 'update'])->name('admin.subscribers.update');
        Route::delete('destroy', [SubscribersController::class, 'destroy'])->name('admin.subscribers.destroy');
        Route::get('import', [SubscribersController::class, 'import'])->name('admin.subscribers.import');
        Route::post('import-subscribers', [SubscribersController::class, 'mportSubscribers'])->name('admin.subscribers.import_subscribers');
        Route::get('export', [SubscribersController::class, 'export'])->name('admin.subscribers.export');
        Route::post('export-subscribers', [SubscribersController::class, 'exportSubscribers'])->name('admin.subscribers.export_subscribers');
        Route::get('remove-all', [SubscribersController::class, 'removeAll'])->name('admin.subscribers.remove_all');
        Route::post('status', [SubscribersController::class, 'status'])->name('admin.subscribers.status');
    });
});

Route::middleware(['permission:admin'])->group(function () {
    Route::group(['prefix' => 'users'], function () {
        Route::get('', [UsersController::class, 'index'])->name('admin.users.index');
        Route::get('create', [UsersController::class, 'create'])->name('admin.users.create');
        Route::post('store', [UsersController::class, 'store'])->name('admin.users.store');
        Route::get('edit/{id}', [UsersController::class, 'edit'])->name('admin.users.edit');
        Route::put('update', [UsersController::class, 'update'])->name('admin.users.update');
        Route::delete('destroy', [UsersController::class, 'destroy'])->name('admin.users.destroy')->where('id', '[0-9]+');
    });
});

Route::group(['prefix' => 'datatable'], function () {
    Route::any('category', [DataTableController::class, 'getCategory'])->name('admin.datatable.category')->middleware(['permission:admin|moderator']);
    Route::any('smtp', [DataTableController::class, 'getSmtp'])->name('admin.datatable.smtp')->middleware(['permission:admin']);
    Route::any('subscribers', [DataTableController::class, 'getSubscribers'])->name('admin.datatable.subscribers')->middleware(['permission:admin|moderator']);
    Route::any('users', [DataTableController::class, 'getUsers'])->name('admin.datatable.users')->middleware(['permission:admin']);
});




