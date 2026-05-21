<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ComponentAsset extends Model
{
    protected $fillable = [
        'page_component_id',
        'asset_type',
        'file_path',
        'original_name',
        'alt_text',
        'mime_type',
        'file_size',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size'  => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The page-component placement this asset belongs to.
     */
    public function pageComponent(): BelongsTo
    {
        return $this->belongsTo(PageComponent::class);
    }

    // -------------------------------------------------------
    // Accessors
    // -------------------------------------------------------

    /**
     * Get the full public URL for this asset.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
