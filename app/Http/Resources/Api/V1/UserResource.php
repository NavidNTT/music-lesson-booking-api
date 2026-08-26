<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\User\Models\User;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_path
                ? url('storage/'.$this->avatar_path)
                : null,
            'role' => $this->role instanceof BackedEnum
                ? $this->role->value
                : $this->role,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
