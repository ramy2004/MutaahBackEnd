<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'saved_at' => $this->created_at,
            'product'  => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
