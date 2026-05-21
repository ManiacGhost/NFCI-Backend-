<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageComponentResource extends JsonResource
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
            'sort_order'       => $this->sort_order,
            'is_visible'       => $this->is_visible,
            'config_overrides' => $this->config_overrides,
            'merged_config'    => $this->merged_config,
            'component'        => $this->whenLoaded('component', function () {
                return [
                    'id'              => $this->component->id,
                    'component_id'    => $this->component->component_id,   // e.g. "CTA 2"
                    'name'            => $this->component->name,
                    'description'     => $this->component->description,
                    'default_config'  => $this->component->default_config,
                    'type'            => $this->when(
                        $this->component->relationLoaded('componentType'),
                        function () {
                            return [
                                'code'        => $this->component->componentType->code,
                                'name'        => $this->component->componentType->name,
                                'description' => $this->component->componentType->description,
                            ];
                        }
                    ),
                ];
            }),
            'assets'           => ComponentAssetResource::collection(
                $this->whenLoaded('assets')
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
