<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LanguageStoreRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            "name" => "required|string",
            "code" => ["required", "unique:languages,code", "regex:/^\S*$/", 'between:2,3'],
            "direction" => "required|string",
            "is_active" => "nullable|boolean",
            "direction" => ['required', 'in:ltr,rtl'], // Validate direction as either 'ltr' or 'rtl'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $rtlLanguages = [
            'arabic',
            'aramaic',
            'azeri',
            'dhivehi_maldivian',
            'hebrew',
            'kurdish_sorani',
            'persian_farsi',
            'urdu'
        ];

        $languageName = strtolower($this->input('name')); // Normalize the input
        $direction = in_array($languageName, $rtlLanguages) ? 'rtl' : 'ltr';

        $this->merge([
            'direction' => $direction, // Dynamically set the direction field
        ]);
    }
}
