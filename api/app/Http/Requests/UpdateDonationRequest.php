<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Donation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $donation = $this->route('donation');

        return $this->user() !== null &&
               ($this->user()->can('update', $donation) ||
                $this->user()->id === $donation->donor_id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'anonymous' => ['sometimes', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['sometimes', 'integer', Rule::in([
                Donation::VISIBILITY_PUBLIC,
                Donation::VISIBILITY_ANONYMOUS,
            ])],
            'status' => ['sometimes', 'integer', Rule::in([
                Donation::STATUS_PENDING,
                Donation::STATUS_COMPLETED,
                Donation::STATUS_FAILED,
                Donation::STATUS_REFUNDED,
            ])],
        ];
    }
}
