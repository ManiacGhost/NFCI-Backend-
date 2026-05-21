<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
            'schema'      => $this->schema,
            'variants'    => $this->whenLoaded('components', function () {
                return $this->components->map(fn ($c) => [
                    'id'              => $c->id,
                    'component_id'    => "{$this->code} {$c->variant_number}",
                    'variant_number'  => $c->variant_number,
                    'name'            => $c->name,
                    'description'     => $c->description,
                    'default_config'  => $c->default_config,
                ]);
            }),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
