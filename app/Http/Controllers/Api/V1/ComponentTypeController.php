<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComponentTypeResource;
use App\Models\Component;
use App\Models\ComponentType;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     * Get a single component type by its code.
     *
     * GET /api/v1/component-types/{code}
     */
    public function show(string $code): JsonResponse
    {
        $type = ComponentType::findByCode($code);

        if (!$type) {
            return $this->notFound("Component type '{$code}' not found.");
        }

        $type->load('components');

        return $this->success(
            new ComponentTypeResource($type),
            'Component type retrieved successfully.'
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
     * Update an existing component type.
     *
     * PUT /api/v1/component-types/{code}
     */
    public function update(Request $request, string $code): JsonResponse
    {
        $type = ComponentType::findByCode($code);

        if (!$type) {
            return $this->notFound("Component type '{$code}' not found.");
        }

        $data = $request->validate([
            'code'        => ['sometimes', 'string', 'max:50', Rule::unique('component_types', 'code')->ignore($type->id)],
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'schema'      => ['sometimes', 'nullable', 'array'],
        ]);

        // Force uppercase code if being updated
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $type->update($data);

        return $this->success(
            new ComponentTypeResource($type->fresh()->load('components')),
            "Component type '{$type->code}' updated."
        );
    }

    /**
     * Delete a component type and all its variants.
     *
     * DELETE /api/v1/component-types/{code}
     */
    public function destroy(string $code): JsonResponse
    {
        $type = ComponentType::findByCode($code);

        if (!$type) {
            return $this->notFound("Component type '{$code}' not found.");
        }

        // Check if any variants are attached to pages
        $inUseCount = Component::where('component_type_id', $type->id)
            ->whereHas('pageComponents')
            ->count();

        if ($inUseCount > 0) {
            return $this->error(
                "Cannot delete '{$code}': {$inUseCount} variant(s) are currently attached to pages. Remove them first.",
                409
            );
        }

        // Delete all variants first, then the type
        $type->components()->delete();
        $type->delete();

        return $this->success(null, "Component type '{$code}' and all its variants deleted.");
    }

    // -------------------------------------------------------
    // Variants
    // -------------------------------------------------------

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

    /**
     * Update an existing variant.
     *
     * PUT /api/v1/component-types/{code}/variants/{variantNumber}
     */
    public function updateVariant(Request $request, string $code, int $variantNumber): JsonResponse
    {
        $type = ComponentType::findByCode($code);

        if (!$type) {
            return $this->notFound("Component type '{$code}' not found.");
        }

        $component = Component::where('component_type_id', $type->id)
            ->where('variant_number', $variantNumber)
            ->first();

        if (!$component) {
            return $this->notFound("Variant {$variantNumber} not found for '{$code}'.");
        }

        $data = $request->validate([
            'variant_number' => ['sometimes', 'integer', 'min:1'],
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['sometimes', 'nullable', 'string'],
            'default_config' => ['sometimes', 'nullable', 'array'],
        ]);

        // Check for duplicate variant number if being changed
        if (isset($data['variant_number']) && $data['variant_number'] !== $variantNumber) {
            $exists = Component::where('component_type_id', $type->id)
                ->where('variant_number', $data['variant_number'])
                ->exists();

            if ($exists) {
                return $this->error(
                    "Variant {$data['variant_number']} already exists for {$type->code}.",
                    409
                );
            }
        }

        $component->update($data);
        $component->refresh();

        return $this->success([
            'id'             => $component->id,
            'component_id'   => "{$type->code} {$component->variant_number}",
            'name'           => $component->name,
            'description'    => $component->description,
            'default_config' => $component->default_config,
        ], "Component '{$type->code} {$component->variant_number}' updated.");
    }

    /**
     * Delete a variant from a component type.
     *
     * DELETE /api/v1/component-types/{code}/variants/{variantNumber}
     */
    public function destroyVariant(string $code, int $variantNumber): JsonResponse
    {
        $type = ComponentType::findByCode($code);

        if (!$type) {
            return $this->notFound("Component type '{$code}' not found.");
        }

        $component = Component::where('component_type_id', $type->id)
            ->where('variant_number', $variantNumber)
            ->first();

        if (!$component) {
            return $this->notFound("Variant {$variantNumber} not found for '{$code}'.");
        }

        // Check if the variant is attached to any pages
        $inUseCount = $component->pageComponents()->count();

        if ($inUseCount > 0) {
            return $this->error(
                "Cannot delete '{$code} {$variantNumber}': it is attached to {$inUseCount} page(s). Remove it from pages first.",
                409
            );
        }

        $component->delete();

        return $this->success(null, "Component '{$code} {$variantNumber}' deleted.");
    }
}
