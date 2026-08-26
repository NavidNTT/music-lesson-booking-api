<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Booking\Models\Booking;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->with(['teacherProfile.user', 'studentProfile.user', 'timeSlot', 'review'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->through(fn ($b) => new BookingResource($b));

        return $this->success(data: $bookings);
    }
}