<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentAssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'asset_type'    => $this->asset_type,
            'url'           => $this->url,
            'original_name' => $this->original_name,
            'alt_text'      => $this->alt_text,
            'mime_type'     => $this->mime_type,
            'file_size'     => $this->file_size,
            'sort_order'    => $this->sort_order,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
