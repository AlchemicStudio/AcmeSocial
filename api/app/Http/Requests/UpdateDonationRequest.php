<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Donation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;

class UpdateDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        if ($this->user() === null) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $donation = $this->route('donation');

        return ($this->user()->can('update', $donation) ||
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
