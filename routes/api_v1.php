<?php

use App\Http\Controllers\Api\V1\Admin\InstrumentController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\TokenRefreshController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Booking\TeacherBookingController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Review\ReviewController;
use App\Http\Controllers\Api\V1\Student\BookingController;
use App\Http\Controllers\Api\V1\Student\CancelBookingController;
use App\Http\Controllers\Api\V1\Teacher\PublicTeacherController;
use App\Http\Controllers\Api\V1\Teacher\TeacherProfileController;
use App\Http\Controllers\Api\V1\Teacher\TeacherTimeSlotController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\Wallet\DepositController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        // -- Auth (public) --
        Route::post('/auth/register', [AuthController::class, 'register'])
            ->middleware('throttle:5,1')
            ->name('auth.register');
        Route::post('/auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('auth.login');

        Route::post('/forgot-password', ForgotPasswordController::class)
            ->middleware('throttle:5,1')
            ->name('password.forgot');
        Route::post('/reset-password', ResetPasswordController::class)
            ->middleware('throttle:5,1')
            ->name('password.reset');

        // -- Public browse --
        Route::get('/instruments', [InstrumentController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('instruments.index');

        Route::get('/teachers', [PublicTeacherController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('teachers.index');
        Route::get('/teachers/{teacher}', [PublicTeacherController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('teachers.show');
        Route::get('/teachers/{teacher}/slots', [PublicTeacherController::class, 'slots'])
            ->middleware('throttle:60,1')
            ->name('teachers.slots');

        Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        // -- Authenticated (any role) --
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('/auth/refresh', TokenRefreshController::class)->name('auth.refresh');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::patch('/me', [ProfileController::class, 'update'])->name('me.update');

            Route::post('/uploads', [UploadController::class, 'store'])
                ->middleware('throttle:30,1')
                ->name('uploads.store');

            Route::post('/email/resend', [VerifyEmailController::class, 'resend'])
                ->middleware('throttle:3,1')
                ->name('verification.resend');

            Route::post('/reviews', [ReviewController::class, 'store'])
                ->middleware('throttle:10,1')
                ->name('reviews.store');
        });

        // -- Admin --
        Route::middleware(['auth:sanctum', 'role:admin'])
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::get('/stats', AdminDashboardController::class)->name('stats');
                Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
                Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
                Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
                Route::apiResource('instruments', InstrumentController::class)
                    ->middleware('throttle:60,1');
            });

        // -- Teacher --
        Route::middleware(['auth:sanctum', 'role:teacher'])
            ->prefix('teacher')
            ->name('teacher.')
            ->group(function () {
                Route::get('/profile', [TeacherProfileController::class, 'show'])->name('profile.show');
                Route::patch('/profile', [TeacherProfileController::class, 'update'])->name('profile.update');
                Route::post('/profile/instruments', [TeacherProfileController::class, 'syncInstruments'])
                    ->name('profile.instruments.sync');

                Route::get('/bookings', [TeacherBookingController::class, 'index'])
                    ->middleware('throttle:30,1')
                    ->name('bookings.index');
                Route::post('/bookings/{booking}/confirm', [TeacherBookingController::class, 'confirm'])
                    ->middleware('throttle:20,1')
                    ->name('bookings.confirm');
                Route::post('/bookings/{booking}/complete', [TeacherBookingController::class, 'complete'])
                    ->middleware('throttle:20,1')
                    ->name('bookings.complete');
                Route::post('/bookings/{booking}/cancel', [TeacherBookingController::class, 'cancel'])
                    ->middleware('throttle:20,1')
                    ->name('bookings.cancel');

                Route::get('/slots', [TeacherTimeSlotController::class, 'index'])
                    ->middleware('throttle:30,1')
                    ->name('slots.index');
                Route::post('/slots', [TeacherTimeSlotController::class, 'store'])
                    ->middleware('throttle:30,1')
                    ->name('slots.store');
                Route::get('/slots/{slot}', [TeacherTimeSlotController::class, 'show'])
                    ->middleware('throttle:30,1')
                    ->name('slots.show');
                Route::patch('/slots/{slot}', [TeacherTimeSlotController::class, 'update'])
                    ->middleware('throttle:30,1')
                    ->name('slots.update');
                Route::delete('/slots/{slot}', [TeacherTimeSlotController::class, 'destroy'])
                    ->middleware('throttle:30,1')
                    ->name('slots.destroy');
            });

        // -- Student --
        Route::middleware(['auth:sanctum', 'role:student'])
            ->prefix('student')
            ->name('student.')
            ->group(function () {
                Route::post('/bookings', [BookingController::class, 'store'])
                    ->middleware('throttle:10,1')
                    ->name('bookings.store');
                Route::get('/bookings', [BookingController::class, 'index'])
                    ->middleware('throttle:30,1')
                    ->name('bookings.index');
                Route::post('/bookings/{booking}/cancel', CancelBookingController::class)
                    ->middleware('throttle:10,1')
                    ->name('bookings.cancel');
            });

        // -- Wallet (authenticated) --
        Route::middleware('auth:sanctum')
            ->prefix('wallet')
            ->name('wallet.')
            ->group(function () {
                Route::get('/', [WalletController::class, 'me'])
                    ->middleware('throttle:30,1')
                    ->name('me');

                Route::post('/deposit', [DepositController::class, 'store'])
                    ->middleware('throttle:10,1')
                    ->name('deposit.store');
            });
    });
