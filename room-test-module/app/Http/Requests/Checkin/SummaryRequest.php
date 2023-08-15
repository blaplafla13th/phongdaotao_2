<?php

namespace App\Http\Requests\Checkin;

use Illuminate\Foundation\Http\FormRequest;

class SummaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "shifts" => [
                "array"
            ],
            "from" => [
                "date_format:Y/m/d"
            ],
            "to" => [
                "date_format:Y/m/d"
            ],
            "supervisors" => [
                "array"
            ],
        ];
    }
}
