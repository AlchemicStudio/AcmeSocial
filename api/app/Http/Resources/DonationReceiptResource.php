<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DonationReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'donation_id' => $this->resource->donation_id,
            'receipt_number' => $this->resource->receipt_number,
            'issued_date' => $this->resource->issued_date->format('Y-m-d'),
            'file_url' => $this->resource->getMedia(DonationReceipt::RECEIPT_MEDIA_COLLECTION)->first()?->getUrl(),
            'email_sent_at' => $this->resource->email_sent,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'donation' => new DonationResource($this->whenLoaded('donation')),
        ];
    }
}
