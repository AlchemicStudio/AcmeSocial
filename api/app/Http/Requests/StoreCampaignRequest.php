<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
        $statusValues = [
            Campaign::STATUS_DRAFT,
            Campaign::STATUS_PENDING,
            Campaign::STATUS_APPROVED,
            Campaign::STATUS_REJECTED,
            Campaign::STATUS_COMPLETED,
            Campaign::STATUS_CANCELLED,
        ];

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'goal_amount' => ['required', 'integer', 'min:1'],
            'current_amount' => ['sometimes', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'integer', 'in:'.implode(',', $statusValues)],
            'creator_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'cover_image_url' => ['nullable', 'url'],
            'video_url' => ['nullable', 'url'],
            'approved_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'uuid', 'exists:users,id'],
            'rejected_by' => ['nullable', 'uuid', 'exists:users,id'],
            'rejected_reason' => ['nullable', 'string'],
        ];
    }
}
