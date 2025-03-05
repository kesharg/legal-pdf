<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryUpdateRequest extends FormRequest
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
            'code' => 'required|size:2|unique:countries,code,' . $this->country->id,
            "language_code" => "required|exists:languages,code",
            "currency" => "required|exists:currencies,code",
            "is_enable"     => "required",
            "sub_domain_prefix" => [
                "required",
                "size:2",
                Rule::unique('countries', 'sub_domain_prefix')->ignore($this->country->id),
            ],        ];
    }

    public function getData()
    {
        $data              = $this->validated();
        return $data;
    }
}
