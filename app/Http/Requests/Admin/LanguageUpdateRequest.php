<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name"      => "required",
            "code"      => ["required", Rule::unique('languages', 'code')->ignore($this->language), 'between:2,3'],
            "direction" => "required|string",
            "is_active" => "nullable",
            "direction" => ['required','string'],
        ];
    }
}
