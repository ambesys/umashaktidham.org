<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\AdminController;

// Public Routes
Route::get('/', function () {
    return view('layouts.header') . view('welcome') . view('layouts.footer');
});

Route::get('/donate', [DonationController::class, 'showDonationPage'])->name('donate');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// User Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/members/profile', [MemberController::class, 'showProfile'])->name('members.profile');
    Route::get('/members/family', [FamilyController::class, 'showFamilyDetails'])->name('members.family');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'manageUsers'])->name('admin.users');
    Route::get('/admin/moderators', [AdminController::class, 'manageModerators'])->name('admin.moderators');
    Route::post('/admin/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
    Route::post('/admin/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
});