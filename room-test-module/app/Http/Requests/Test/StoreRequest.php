<?php

namespace App\Http\Requests\Test;

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
            "room_detail_id" => [
                "required",
                "integer",
                "exists:room_details,id",
            ],
            "shift_id" => [
                "required",
                "integer",
                "exists:shifts,id",
            ],
            Rule::unique('room_tests')->where(function ($query) {
                return $query->where([
                    'room_detail_id', "=", $this->room_detail_id,
                    'shift_id', "=", $this->shift_id
                ]);
            }),
            "need_supervisor" => [
                "required",
                "integer",
                "min:1",
                "max:3"
            ],
            "quantity" => [
                "required",
                "integer",
            ],
            "exam_test_id" => [
                "required",
                "integer",
            ],
        ];
    }
}
