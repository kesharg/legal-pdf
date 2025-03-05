<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CountryStoreRequest extends FormRequest
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
            "name"   => "required",
            'code' => 'required|size:2|unique:countries',
            "language_code" => "required|exists:languages,code",
            "currency" => "required|exists:currencies,code",
            'sub_domain_prefix' => 'required|size:2|unique:countries,sub_domain_prefix'
        ];
    }

    public function getData()
    {
        $data              = $this->validated();
        $data["is_enable"] = 1;
        return $data;
    }
}
