<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageComponent extends Model
{
    protected $fillable = [
        'page_id',
        'component_id',
        'sort_order',
        'config_overrides',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'sort_order'       => 'integer',
            'config_overrides' => 'array',
            'is_visible'       => 'boolean',
        ];
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The page this component is placed on.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * The component definition.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Assets attached to this page-component placement.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(ComponentAsset::class)->orderBy('sort_order');
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Compute the merged config: component defaults + page overrides.
     *
     * @return array<string, mixed>
     */
    public function getMergedConfigAttribute(): array
    {
        $defaults  = $this->component->default_config ?? [];
        $overrides = $this->config_overrides ?? [];

        return array_replace_recursive($defaults, $overrides);
    }
}
