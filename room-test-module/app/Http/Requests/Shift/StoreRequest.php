<?php

namespace App\Http\Requests\Shift;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            "shift_start_time" => [
                "required",
                "date_format:Y/m/d H:i:s",
                "after:yesterday",
                Rule::unique("shifts")->where(function ($query) {
                    return $query->where([
                        "shift_start_time" => $this->shift_start_time,
                    ]);
                })
            ],
            "master" => [
                "integer",
            ],
        ];
    }
}
