<?php

namespace App\Http\Requests\Shift;


use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;

class StartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return get_user()->id === Shift::query()->where("url", $this->url)->first()->master;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "link_end_time" => [
                "required",
                "date_format:Y/m/d H:i:s",
                "after:now",
            ],
            "url" => [
                "required",
                "exists:shifts,url",
            ]
        ];
    }
}
