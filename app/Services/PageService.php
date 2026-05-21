<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Page;
use App\Models\PageComponent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageService
{
    /**
     * List all pages, paginated.
     */
    public function listPages(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = Page::with('creator')
            ->withCount('pageComponents');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('page_number')->paginate($perPage);
    }

    /**
     * Get a single page by its page number, fully loaded with components and assets.
     */
    public function getPageByNumber(int $pageNumber): ?Page
    {
        return Page::byPageNumber($pageNumber)
            ->with([
                'creator:id,name,email',
                'pageComponents' => function ($q) {
                    $q->orderBy('sort_order');
                },
                'pageComponents.component.componentType',
                'pageComponents.assets',
            ])
            ->first();
    }

    /**
     * Create a new page, optionally attaching components.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>|null    $componentIds  Compound IDs like ["CTA 2", "IMGGAL 3"]
     */
    public function createPage(array $data, ?array $componentIds = null): Page
    {
        return DB::transaction(function () use ($data, $componentIds) {
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']) . '-' . $data['page_number'];
            }

            $page = Page::create($data);

            // Attach components if provided
            if (!empty($componentIds)) {
                $this->attachComponentsByCompoundIds($page, $componentIds);
            }

            return $page->load([
                'pageComponents.component.componentType',
                'pageComponents.assets',
            ]);
        });
    }

    /**
     * Update page metadata.
     *
     * @param  array<string, mixed> $data
     */
    public function updatePage(int $pageNumber, array $data): ?Page
    {
        $page = Page::byPageNumber($pageNumber)->first();

        if (!$page) {
            return null;
        }

        $page->update($data);

        return $page->fresh([
            'creator:id,name,email',
            'pageComponents.component.componentType',
            'pageComponents.assets',
        ]);
    }

    /**
     * Soft-delete a page.
     */
    public function deletePage(int $pageNumber): bool
    {
        $page = Page::byPageNumber($pageNumber)->first();

        if (!$page) {
            return false;
        }

        return (bool) $page->delete();
    }

    /**
     * Attach components to a page using compound IDs.
     *
     * @param  array<int, array{component_id: string, sort_order?: int, config_overrides?: array, is_visible?: bool}> $components
     * @return array{attached: array, errors: array}
     */
    public function attachComponents(Page $page, array $components): array
    {
        $attached = [];
        $errors   = [];

        DB::transaction(function () use ($page, $components, &$attached, &$errors) {
            $maxSortOrder = $page->pageComponents()->max('sort_order') ?? 0;

            foreach ($components as $index => $item) {
                $component = Component::resolveFromCompoundId($item['component_id']);

                if (!$component) {
                    $errors[] = [
                        'index'        => $index,
                        'component_id' => $item['component_id'],
                        'error'        => "Component '{$item['component_id']}' not found.",
                    ];
                    continue;
                }

                $maxSortOrder++;

                $pageComponent = PageComponent::create([
                    'page_id'          => $page->id,
                    'component_id'     => $component->id,
                    'sort_order'       => $item['sort_order'] ?? $maxSortOrder,
                    'config_overrides' => $item['config_overrides'] ?? null,
                    'is_visible'       => $item['is_visible'] ?? true,
                ]);

                $pageComponent->load('component.componentType', 'assets');
                $attached[] = $pageComponent;
            }
        });

        return compact('attached', 'errors');
    }

    /**
     * Remove a component from a page.
     */
    public function removeComponent(int $pageComponentId): bool
    {
        $pc = PageComponent::find($pageComponentId);

        if (!$pc) {
            return false;
        }

        return (bool) $pc->delete();
    }

    /**
     * Update a page component (config, visibility, sort order).
     *
     * @param  array<string, mixed> $data
     */
    public function updatePageComponent(int $pageComponentId, array $data): ?PageComponent
    {
        $pc = PageComponent::find($pageComponentId);

        if (!$pc) {
            return null;
        }

        $pc->update($data);

        return $pc->fresh('component.componentType', 'assets');
    }

    /**
     * Bulk reorder components on a page.
     *
     * @param  array<int, array{id: int, sort_order: int}> $order
     */
    public function reorderComponents(array $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order as $item) {
                PageComponent::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });
    }

    // -------------------------------------------------------
    // Internal
    // -------------------------------------------------------

    /**
     * Attach components from a flat array of compound IDs (e.g. ["CTA 2", "IMGGAL 3"]).
     *
     * @param  array<string> $compoundIds
     */
    private function attachComponentsByCompoundIds(Page $page, array $compoundIds): void
    {
        foreach ($compoundIds as $sortOrder => $compoundId) {
            $component = Component::resolveFromCompoundId($compoundId);

            if ($component) {
                PageComponent::create([
                    'page_id'      => $page->id,
                    'component_id' => $component->id,
                    'sort_order'   => $sortOrder + 1,
                    'is_visible'   => true,
                ]);
            }
        }
    }
}
