<?php

namespace App\Http\Requests\Checkin;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return get_user()->id === Shift::query()->where("url", $this->route('id'))->first()->master;
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
                "integer",
            ],
            "position" => [
                "required",
                "string",
                "unique:room_details,name"
            ],
            Rule::unique('checkins')->where(function ($query) {
                $shift = Shift::query()->where('url',
                    request()->route('id')
                )->firstOrFail();
                return $query->where([
                    'supervisor', "=", $this->supervisor,
                    'shift_id', '=', $shift->id
                ])->whereOr([
                    'position', "=", $this->position,
                    'shift_id', '=', $shift->id
                ]);
            }),
        ];
    }
}
