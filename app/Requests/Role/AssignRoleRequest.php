<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::ROLES_ASSIGN->value);
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::exists('roles', 'name')],
        ];
    }
}