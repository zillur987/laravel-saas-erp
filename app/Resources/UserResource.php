<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roleNames(),
            // Flattened + deduped list of every permission this user has,
            // whether inherited from a role or granted directly.
            // The Vue store consumes this array directly — no need to
            // resolve role -> permission mapping on the client.
            'permissions' => $this->allPermissionNames(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}