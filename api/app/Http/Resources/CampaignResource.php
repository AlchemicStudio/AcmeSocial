<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Campaign $resource
 */
class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLabels = [
            Campaign::STATUS_DRAFT => 'draft',
            Campaign::STATUS_PENDING => 'pending',
            Campaign::STATUS_APPROVED => 'approved',
            Campaign::STATUS_REJECTED => 'rejected',
            Campaign::STATUS_COMPLETED => 'completed',
            Campaign::STATUS_CANCELLED => 'cancelled',
        ];

        return [
            'id' => $this->resource->getKey(),
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'goal_amount' => $this->resource->goal_amount,
            'current_amount' => $this->resource->current_amount,
            'start_date' => $this->resource->start_date?->toDateString(),
            'end_date' => $this->resource->end_date?->toDateString(),
            'status' => $this->resource->status,
            'status_label' => $statusLabels[$this->resource->status] ?? null,
            'creator_id' => $this->resource->creator_id,
            'logo' => $this->resource->getMedia(Campaign::LOGO_MEDIA_COLLECTION)->first()?->getUrl(),
            'medias' => $this->resource->getMedia(Campaign::OTHER_MEDIA_COLLECTION)->map(function ($media) {
                return [
                    'id' => $media->getKey(),
                    'url' => $media->getUrl(),
                    'type' => $media->mime_type,
                ];
            }),
            'approved_at' => $this->resource->approved_at?->toAtomString(),
            'approved_by' => $this->resource->approved_by,
            'rejected_by' => $this->resource->rejected_by,
            'rejected_at' => $this->resource->rejected_at?->toAtomString(),
            'rejected_reason' => $this->resource->rejected_reason,

            'creator' => $this->whenLoaded('creator', function (User $user) {
                return [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
            'approver' => $this->whenLoaded('approver', function (User $user) {
                return [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
            'rejector' => $this->whenLoaded('rejector', function (User $user) {
                return [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),

            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
            'deleted_at' => $this->resource->deleted_at?->toAtomString(),
        ];
    }
}
