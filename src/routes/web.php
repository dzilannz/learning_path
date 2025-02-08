<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IbtitahController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomLoginController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\AdminViewController;
use App\Http\Controllers\SearchAdminController;

// Halaman utama (Landing Page)
Route::get('/', [LandingPageController::class, 'index'])->name('landing');

// Halaman login untuk mahasiswa
Route::get('/login', function () {
    return view('login'); // resources/views/login.blade.php
})->name('login.form');

// Proses login mahasiswa
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Rute khusus mahasiswa
Route::middleware([\App\Http\Middleware\AuthMiddleware::class . ':mahasiswa'])->group(function () {
    // Dashboard mahasiswa
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

    // Submit proof oleh mahasiswa
    Route::post('/ibtitah/submit', [IbtitahController::class, 'submitProof'])->name('ibtitah.submit');
});


Route::middleware([\App\Http\Middleware\AuthMiddleware::class . ':admin'])->group(function () {
    // Dashboard admin
    Route::get('/admin/dashboard', [AdminViewController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/profile', [AdminController::class, 'showAllFiles'])->name('admin.profile');

    Route::get('/admin/upload', function () {
        return view('admin.upload'); // resources/views/admin/upload.blade.php
    })->name('admin.upload');

    Route::get('/admin/search', function () {
        return view('admin.search'); // Tampilkan form pencarian
    })->name('admin.search');

    Route::post('/admin/upload', [AdminController::class, 'uploadFile'])->name('admin.uploadFile');

    // Proses approve file
    Route::patch('/admin/approve/{id}', [AdminController::class, 'approve'])->name('admin.approve');

    // Proses reject file
    Route::patch('/admin/reject/{id}', [AdminController::class, 'reject'])->name('admin.reject');

    // Proses hapus file
    Route::delete('/admin/delete/{id}', [AdminController::class, 'delete'])->name('admin.delete');

// Route untuk pencarian berdasarkan NIM
    Route::get('/admin/search/result', [AdminController::class, 'search'])->name('admin.search.result');
    

});



// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');




