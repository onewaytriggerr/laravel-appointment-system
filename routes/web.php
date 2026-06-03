<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Models\Branch;
use App\Enums\UserRole;

Route::get('/', [BookingController::class, 'show'])->name('booking.form');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
Route::get('/booking/success', [BookingController::class, 'success'])->name('booking.success');

// API route to get staff members for a specific branch
Route::get('/branches/{branch}/staff', function (Branch $branch) {
    return $branch->staff()->where('role', UserRole::Staff)->get(['id', 'name']);
})->name('branches.staff');