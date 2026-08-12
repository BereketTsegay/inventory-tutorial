<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DynamicFormController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('admin.login');
})->name('login');
Route::get('/register', function () {
    return view('admin.register');
})->name('register');

Route::get('/dashboard', function () {
    return view('admin.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/form/{table}/dashboard', [DynamicFormController::class, 'index'])->name('dynamic.index');

Route::middleware('auth')->group(function () {
    Route::get('/logout', [App\Http\Controllers\Auth\AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/profile', [App\Http\Controllers\Auth\AdminController::class, 'profile'])->name('admin.profile');
    Route::post('/profile/update', [App\Http\Controllers\Auth\AdminController::class, 'updateProfile'])->name('admin.profileUpdate');
    Route::post('/profile/change-password', [App\Http\Controllers\Auth\AdminController::class, 'changePassword'])->name('admin.profile.change-password');

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::controller(DynamicFormController::class)->group(function () {
        // Form views
        Route::get('/form/{table}', 'create')->name('dynamic.form.create');
        Route::get('/form/{table}/{id}/edit', 'edit')->name('dynamic.form.edit');

        // Form submissions
        Route::post('/form/{table}', 'store')->name('dynamic.form.store');
        Route::put('/form/{table}/{id}', 'update')->name('dynamic.form.update');
        Route::delete('/form/{table}/{id}', 'destroy')->name('dynamic.form.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::controller(BrandController::class)->group(function () {
        Route::get('/brand/all', 'AllBrand')->name('all.brand');
        Route::get('/brand/add/{id?}', 'FormBrand')->name('form.brand');
        Route::post('/brand/store', 'StoreBrand')->name('store.brand');
        Route::get('/brand/delete/{id}', 'DeleteBrand')->name('delete.brand');
    });
    Route::controller(\App\Http\Controllers\WareHouseController::class)->group(function () {
        Route::get('/warehouse/all', 'AllWareHouse')->name('all.warehouse');
    });
});

require __DIR__.'/auth.php';
