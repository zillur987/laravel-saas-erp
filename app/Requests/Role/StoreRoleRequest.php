<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::ROLES_CREATE->value);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Role name may only contain lowercase letters, numbers, and hyphens (e.g. "warehouse-supervisor").',
        ];
    }
}