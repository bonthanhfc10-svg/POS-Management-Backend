<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'role_id' => [
                'sometimes',
                'integer',
                'exists:roles,id',
            ],

            'staff_id' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('users', 'staff_id')->ignore($userId),
            ],

            'full_name' => [
                'sometimes',
                'string',
                'max:150',
            ],

            'username' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($userId),
            ],

            'email' => [
                'sometimes',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
            ],

            'status' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
