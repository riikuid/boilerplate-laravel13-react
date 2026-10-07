<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Permissions\UserPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(UserPermission::Update->value) === true;
    }

    protected function prepareForValidation(): void
    {
        // An HTML form sends nothing when every role checkbox is cleared: absent means "no roles".
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'roles' => $this->input('roles', []),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user instanceof User ? $user->id : null),
            ],
            'password' => ['nullable', 'string', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
