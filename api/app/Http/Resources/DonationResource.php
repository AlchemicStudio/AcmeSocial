<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Donation $resource
 */
class DonationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLabels = [
            Donation::STATUS_PENDING => 'pending',
            Donation::STATUS_COMPLETED => 'completed',
            Donation::STATUS_FAILED => 'failed',
            Donation::STATUS_REFUNDED => 'refunded',
        ];

        $visibilityLabels = [
            Donation::VISIBILITY_PUBLIC => 'public',
            Donation::VISIBILITY_PRIVATE => 'private',
        ];

        return [
            'id' => $this->resource->getKey(),
            'campaign_id' => $this->resource->campaign_id,
            'donor_id' => $this->resource->donor_id,
            'amount' => $this->resource->amount,
            'currency' => $this->resource->currency,
            'anonymous' => $this->resource->anonymous,
            'message' => $this->resource->message,
            'visibility' => $this->resource->visibility,
            'visibility_label' => $visibilityLabels[$this->resource->visibility] ?? null,
            'status' => $this->resource->status,
            'status_label' => $statusLabels[$this->resource->status] ?? null,

            'campaign' => $this->whenLoaded('campaign', function (Campaign $campaign) {
                return [
                    'id' => $campaign->getKey(),
                    'title' => $campaign->title,
                    'description' => $campaign->description,
                    'goal_amount' => $campaign->goal_amount,
                    'current_amount' => $campaign->current_amount,
                ];
            }),
            'donor' => $this->when(!$this->resource->anonymous && $this->resource->visibility === Donation::VISIBILITY_PUBLIC, function () {
                return $this->whenLoaded('donor', function (User $user) {
                    return [
                        'id' => $user->getKey(),
                        'name' => $user->name,
                    ];
                });
            }),

            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
