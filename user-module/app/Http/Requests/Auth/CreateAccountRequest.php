<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|between:2,100',
            'email'    => [
                'required',
                'email',
                'unique:users,email'
            ],
            'phone'    => [
                'required',
                'string',
                'regex:/[0-9]{10}/',
                'unique:users,phone'
            ],
            'password' => [
                'required',
                'string',
                'min:10',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'role'     => [
                'required',
                Rule::in([
                    UserType::Administrator,
                    UserType::Teacher,
                    UserType::Employee,
                ]),
            ],
            'department_id' => [
                'numeric',
                Rule::exists('departments', 'id')
            ]
        ];
    }
}
