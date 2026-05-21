<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'page_number'      => $this->page_number,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'meta_description' => $this->meta_description,
            'status'           => $this->status,
            'created_by'       => $this->whenLoaded('creator', function () {
                return [
                    'id'    => $this->creator->id,
                    'name'  => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'components_count' => $this->when(
                isset($this->page_components_count),
                $this->page_components_count
            ),
            'components'       => PageComponentResource::collection(
                $this->whenLoaded('pageComponents')
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
