<?php

namespace App\Http\Requests\Admin\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeatureUpdateRequest extends FormRequest
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
            "title"   => "required",
            "slug"   => "required",
            "order"   => "required|gt:0",
            "icon_class"   => "nullable",
            "facebook_link"   => "nullable",
            "youtube_link"   => "nullable",
            "instagram_link"   => "nullable",
            "twitter_link"   => "nullable",
           // "short_description"   => "required",
            "description"   => "nullable",
            "is_active"   => "required",
            "feature_image" => "nullable|image|".imageMimes(),
        ];
    }

    public function getData()
    {
        $data              = $this->validated();
        return $data;
    }
}
