<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemovePermissionsRequest extends FormRequest
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
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permissions.required' => 'At least one permission is required for removal.',
            'permissions.array' => 'Permissions must be provided as an array.',
            'permissions.min' => 'At least one permission is required for removal.',
            'permissions.*.required' => 'Each permission name is required.',
            'permissions.*.string' => 'Each permission name must be a string.',
            'permissions.*.exists' => 'The permission ":input" does not exist.',
        ];
    }
}
