<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::ROLES_UPDATE->value);
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'sometimes', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ];
    }
}