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
            'id' => $this->id,
            'donation_id' => $this->donation_id,
            'transaction_reference' => $this->transaction_reference,
            'payment_gateway' => $this->payment_gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'fee_amount' => $this->fee_amount,
            'status' => $this->status,
            'status_message' => $this->status_message,
            'processed_at' => $this->processed_at?->format('c'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'donation' => new DonationResource($this->whenLoaded('donation')),
        ];
    }
}
