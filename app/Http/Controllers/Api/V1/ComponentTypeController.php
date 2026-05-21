<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComponentTypeResource;
use App\Models\Component;
use App\Models\ComponentType;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComponentTypeController extends Controller
{
    use ApiResponse;

    /**
     * List all component types with their variants.
     *
     * GET /api/v1/component-types
     */
    public function index(): JsonResponse
    {
        $types = ComponentType::with('components')->orderBy('code')->get();

        return $this->success(
            ComponentTypeResource::collection($types),
            'Component types retrieved successfully.'
        );
    }

    /**
     * Register a new component type.
     *
     * POST /api/v1/component-types
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:component_types,code'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'schema'      => ['nullable', 'array'],
        ]);

        // Force uppercase code
        $data['code'] = strtoupper($data['code']);

        $type = ComponentType::create($data);

        return $this->created(
            new ComponentTypeResource($type->load('components')),
            "Component type '{$type->code}' registered."
        );
    }

    /**
     * Register a new variant for a component type.
     *
     * POST /api/v1/component-types/{code}/variants
     */
    public function storeVariant(Request $request, string $code): JsonResponse
    {
        $type = ComponentType::findByCode($code);

        if (!$type) {
            return $this->notFound("Component type '{$code}' not found.");
        }

        $data = $request->validate([
            'variant_number' => ['required', 'integer', 'min:1'],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'default_config' => ['nullable', 'array'],
        ]);

        // Check for duplicate variant
        $exists = Component::where('component_type_id', $type->id)
            ->where('variant_number', $data['variant_number'])
            ->exists();

        if ($exists) {
            return $this->error(
                "Variant {$data['variant_number']} already exists for {$type->code}.",
                409
            );
        }

        $data['component_type_id'] = $type->id;
        $component = Component::create($data);

        return $this->created([
            'id'             => $component->id,
            'component_id'   => "{$type->code} {$component->variant_number}",
            'name'           => $component->name,
            'description'    => $component->description,
            'default_config' => $component->default_config,
        ], "Component '{$type->code} {$component->variant_number}' created.");
    }
}
