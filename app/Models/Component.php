<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Component extends Model
{
    protected $fillable = [
        'component_type_id',
        'variant_number',
        'name',
        'description',
        'default_config',
    ];

    protected function casts(): array
    {
        return [
            'variant_number' => 'integer',
            'default_config' => 'array',
        ];
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The component type this variant belongs to.
     */
    public function componentType(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class);
    }

    /**
     * All page placements of this component.
     */
    public function pageComponents(): HasMany
    {
        return $this->hasMany(PageComponent::class);
    }

    // -------------------------------------------------------
    // Accessors
    // -------------------------------------------------------

    /**
     * Get the compound component ID (e.g. "CTA 2", "IMGGAL 3").
     */
    public function getComponentIdAttribute(): string
    {
        $code = $this->relationLoaded('componentType')
            ? $this->componentType->code
            : $this->componentType()->value('code');

        return "{$code} {$this->variant_number}";
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Resolve a component from a compound ID string like "CTA 2".
     */
    public static function resolveFromCompoundId(string $compoundId): ?self
    {
        $parts = explode(' ', trim($compoundId));

        if (count($parts) !== 2) {
            return null;
        }

        [$typeCode, $variantNumber] = $parts;

        return static::whereHas('componentType', function ($q) use ($typeCode) {
            $q->where('code', strtoupper($typeCode));
        })->where('variant_number', (int) $variantNumber)->first();
    }
}
