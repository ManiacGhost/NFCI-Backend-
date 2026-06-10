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
     *
     * Uses asset() helper with /storage/ prefix for reliable URL generation
     * on shared hosting where the storage symlink may not resolve correctly
     * via Storage::url().
     */
    public function getUrlAttribute(): string
    {
        // Normalise path separators to forward slashes
        $path = str_replace('\\', '/', $this->file_path);

        // Build the URL using the APP_URL base + /storage/ prefix
        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($path, '/');
    }
}
