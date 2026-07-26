<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Spatie\Permission\Models\Permission */
class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'module' => explode('.', $this->name)[0],
            'action' => explode('.', $this->name)[1] ?? null,
        ];
    }
}