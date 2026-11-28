<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],
            'staff_id' => [
                'required',
                'string',
                'max:30',
                'unique:users,staff_id',
            ],
            'full_name' => [
                'required',
                'string',
                'max:150',
            ],
            'username' => [
                'required',
                'string',
                'max:100',
                'unique:users,username',
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                'unique:users,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'password' => [
                'required',
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
