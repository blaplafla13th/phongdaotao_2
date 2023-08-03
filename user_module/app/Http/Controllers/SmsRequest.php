<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Http\FormRequest;

class SmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9\s\.\,\;\:\!\?\-]{1,160}$/'
            ],
        ];
    }
}
