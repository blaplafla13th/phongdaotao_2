<?php

namespace App\Http\Requests\Assignment;

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
            "supervisor" => [
                "required",
                "integer"
            ],
            "shift_id" => [
                "required",
                "integer"
            ],
            Rule::unique('assignments')->where(function ($query) {
                return $query->where([
                    'supervisor', "=", $this->supervisor,
                    'shift_id', "=", $this->shift_id
                ]);
            })
        ];
    }
}
