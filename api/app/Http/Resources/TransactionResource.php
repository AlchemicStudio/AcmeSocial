<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_reference' => $this->resource->transaction_reference,
            'payment_gateway' => $this->resource->payment_gateway,
            'gateway_transaction_id' => $this->resource->gateway_transaction_id,
            'amount' => $this->resource->amount,
            'currency' => $this->resource->currency,
            'fee_amount' => $this->resource->fee_amount,
            'status' => $this->resource->status,
            'status_message' => $this->resource->status_message,
            'processed_at' => $this->resource->processed_at?->format('c'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'donation' => new DonationResource($this->resource->whenLoaded('donation')),
        ];
    }
}
