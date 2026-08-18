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



Route::middleware('auth')->group(function () {
    Route::get('/logout', [App\Http\Controllers\Auth\AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/profile', [App\Http\Controllers\Auth\AdminController::class, 'profile'])->name('admin.profile');
    Route::post('/profile/update', [App\Http\Controllers\Auth\AdminController::class, 'updateProfile'])->name('admin.profileUpdate');
    Route::post('/profile/change-password', [App\Http\Controllers\Auth\AdminController::class, 'changePassword'])->name('admin.profile.change-password');

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth'])->group(function () {
    // Standard CRUD Admin Views
    Route::get('/dashboard/{model}', [DynamicFormController::class, 'index'])->name('dynamic.form.index');
    Route::get('/form/{model}', [DynamicFormController::class, 'create'])->name('dynamic.form.create');
    Route::get('/form/{model}/{id}/edit', [DynamicFormController::class, 'edit'])->name('dynamic.form.edit');
    Route::get('/form/{model}/{id}', [DynamicFormController::class, 'show'])->name('dynamic.form.show');

    // API Endpoint for Dynamic JavaScript Chained Dropdowns
    Route::get('/api/form-relation/{model}/children', [DynamicFormController::class, 'getChildOptions']);

    // Processing Actions
    Route::post('/form/{model}', [DynamicFormController::class, 'store'])->name('dynamic.form.store');
    Route::put('/form/{model}/{id}', [DynamicFormController::class, 'update'])->name('dynamic.form.update');
    Route::delete('/form/{model}/{id}', [DynamicFormController::class, 'destroy'])->name('dynamic.form.destroy');
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
