<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class MailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string',
            'body' => 'required|string'
        ];
    }
}
