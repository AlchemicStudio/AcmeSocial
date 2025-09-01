<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Donation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['sometimes', 'integer', Rule::in([
                Donation::VISIBILITY_PUBLIC,
                Donation::VISIBILITY_ANONYMOUS,
            ])],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'donor_id' => $this->user()?->id,
            'currency' => $this->currency ?? 'USD',
            'visibility' => $this->visibility ?? Donation::VISIBILITY_PUBLIC,
            'status' => Donation::STATUS_PENDING,
        ]);
    }
}
