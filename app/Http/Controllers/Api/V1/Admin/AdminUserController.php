<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                )
            )
            ->latest()
            ->paginate(20)
            ->through(fn ($user) => new UserResource($user));

        return $this->success(data: $users);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(data: new UserResource($user->load(['teacherProfile', 'studentProfile', 'wallet'])));
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        $user->update(['role' => $validated['role']]);

        return $this->success(data: new UserResource($user), message: 'User role updated.');
    }
}