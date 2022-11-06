<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
    DashboardController,
    DataTableController,
    UserController,
    RoleController,
    AuthController,
    ProfileController,
    SettingsController
};


Route::group(['prefix' => 'cp'], function () {

    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('', [DashboardController::class, 'index'])->name('cp.dashbaord.index');

    Route::group(['prefix' => 'users'], function () {
        Route::get('', [UserController::class, 'index'])->name('cp.user.index')->middleware(['permission:admin']);
        Route::get('create', [UserController::class, 'create'])->name('cp.user.create')->middleware(['permission:admin']);
        Route::post('create', [UserController::class, 'store'])->name('cp.user.store')->middleware(['permission:admin']);
        Route::get('edit/{id}', [UserController::class, 'edit'])->name('cp.user.edit')->where('id', '[0-9]+')->middleware(['permission:admin'])->where('id', '[0-9]+');
        Route::put('update', [UserController::class, 'update'])->name('cp.user.update')->middleware(['permission:admin']);
        Route::post('destroy', [UserController::class, 'destroy'])->name('cp.user.destroy')->middleware(['permission:admin'])->where('id', '[0-9]+');
    });

    Route::group(['prefix' => 'role'], function () {
        Route::get('', [RoleController::class, 'index'])->name('cp.role.index')->middleware(['permission:admin']);
        Route::get('create', [RoleController::class, 'create'])->name('cp.role.create')->middleware(['permission:admin']);
        Route::post('create', [RoleController::class, 'store'])->name('cp.role.store')->middleware(['permission:admin']);
        Route::get('edit/{id}', [RoleController::class, 'edit'])->name('cp.role.edit')->where('id', '[0-9]+')->middleware(['permission:admin'])->where('id', '[0-9]+');
        Route::put('update', [RoleController::class, 'update'])->name('cp.role.update')->middleware(['permission:admin']);
        Route::post('destroy', [RoleController::class, 'destroy'])->name('cp.role.destroy')->middleware(['permission:admin'])->where('id', '[0-9]+');
    });

    Route::group(['prefix' => 'profile'], function () {
        Route::get('', [ProfileController::class, 'index'])->name('cp.profile');
        Route::put('update', [ProfileController::class, 'update'])->name('cp.profile.update');
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::get('', [SettingsController::class, 'index'])->name('cp.settings.index')->middleware(['permission:admin']);
        Route::get('create', [SettingsController::class, 'create'])->name('cp.settings.create')->middleware(['permission:admin']);
        Route::post('create', [SettingsController::class, 'store'])->name('cp.settings.store')->middleware(['permission:admin']);
        Route::get('edit/{id}', [SettingsController::class, 'edit'])->name('cp.settings.edit')->where('id', '[0-9]+')->middleware(['permission:admin']);
        Route::put('update', [SettingsController::class, 'update'])->name('cp.settings.update')->middleware(['permission:admin']);
        Route::post('destroy', [SettingsController::class, 'destroy'])->name('cp.settings.destroy')->middleware(['permission:admin']);
    });

    Route::group(['prefix' => 'datatable'], function () {
        Route::any('user', [DataTableController::class, 'getUsers'])->name('cp.datatable.user')->middleware(['permission:admin']);
        Route::any('role', [DataTableController::class, 'getRole'])->name('cp.datatable.role');
        Route::any('settings', [DataTableController::class, 'getSettings'])->name('cp.datatable.settings')->middleware(['permission:admin']);
    });

});
