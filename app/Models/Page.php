<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'page_number',
        'title',
        'slug',
        'meta_description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
        ];
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    /**
     * Scope to only active pages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to find by page number.
     */
    public function scopeByPageNumber($query, int $pageNumber)
    {
        return $query->where('page_number', $pageNumber);
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The user who created this page.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Components attached to this page (ordered by sort_order).
     */
    public function pageComponents(): HasMany
    {
        return $this->hasMany(PageComponent::class)->orderBy('sort_order');
    }
}
