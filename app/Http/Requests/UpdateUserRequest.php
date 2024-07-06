<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'description' => 'string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'gender' => 'nullable|numeric',
            'height' => 'nullable|integer|min:100|max:200',
            'smoking' => 'nullable|integer|in:0,1,2',
            'alcohol' => 'nullable|integer|in:0,1,2',
            'interests' => 'nullable|array',
            'interests.*' => 'exists:interests,id',
        ];
    }
}
