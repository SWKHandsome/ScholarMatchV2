<?php

use App\Http\Controllers\Admin\ReferenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Student\BookmarkController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ScholarshipController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.landing');
})->name('home');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.form', ['register' => false])->name('login');
    Route::view('/register', 'auth.form', ['register' => true])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('role:student')->group(function () {
        Route::get('/recommendations', [ScholarshipController::class, 'index'])->name('recommendations');
        Route::get('/scholarships/{scholarship}', [ScholarshipController::class, 'show'])->name('scholarships.show');
        Route::get('/saved', [ScholarshipController::class, 'saved'])->name('saved');
        Route::post('/saved/{scholarship}', [BookmarkController::class, 'store'])->name('bookmarks.store');
        Route::delete('/saved/{scholarship}', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/admin', App\Http\Controllers\Admin\DashboardController::class)->middleware('role:admin')->name('admin.dashboard');
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/scholarships/{scholarship}/deactivate', [App\Http\Controllers\Admin\ScholarshipController::class, 'deactivate'])->name('scholarships.deactivate');
        Route::resource('scholarships', App\Http\Controllers\Admin\ScholarshipController::class);
        Route::get('/fields', [ReferenceController::class, 'fields'])->name('fields.index');
        Route::post('/fields', [ReferenceController::class, 'storeField'])->name('fields.store');
        Route::put('/fields/{field}', [ReferenceController::class, 'updateField'])->name('fields.update');
        Route::get('/income', [ReferenceController::class, 'income'])->name('income.index');
        Route::put('/income', [ReferenceController::class, 'updateIncome'])->name('income.update');
    });
});
