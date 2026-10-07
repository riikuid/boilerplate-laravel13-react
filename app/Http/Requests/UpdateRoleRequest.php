<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Permissions\RolePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(RolePermission::Update->value) === true;
    }

    protected function prepareForValidation(): void
    {
        // An HTML form sends nothing when every checkbox is cleared: absent means "no permissions".
        $this->merge(['permissions' => $this->input('permissions', [])]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('roles', 'name')->ignore($role instanceof Role ? $role->id : null),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
