<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Domain\Wallet\Models\WalletTransaction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->success(data: [
            'total_users' => User::count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_students' => User::where('role', 'student')->count(),
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'total_deposits' => (float) WalletTransaction::where('type', 'deposit')->where('status', 'success')->sum('amount'),
            'recent_bookings' => Booking::with(['teacherProfile.user', 'studentProfile.user'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'status' => $b->status->value,
                    'student' => $b->studentProfile?->user?->name,
                    'teacher' => $b->teacherProfile?->user?->name,
                    'price' => $b->price_amount,
                ]),
        ]);
    }
}