<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth')->group(function () {
    Route::controller(BrandController::class)->group(function () {
        Route::get('/brand/all', 'AllBrand')->name('all.brand');
        Route::get('/brand/add', 'AddBrand')->name('add.brand');
        Route::post('/brand/store', 'StoreBrand')->name('store.brand');
        Route::get('/brand/edit/{id}', 'EditBrand')->name('edit.brand');
        Route::post('/brand/update', 'UpdateBrand')->name('update.brand');
        Route::get('/brand/delete/{id}', 'DeleteBrand')->name('delete.brand');
    });
});

require __DIR__.'/auth.php';
