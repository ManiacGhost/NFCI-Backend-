<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Page\AttachComponentsRequest;
use App\Http\Requests\Page\ReorderComponentsRequest;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Http\Resources\PageComponentResource;
use App\Http\Resources\PageResource;
use App\Services\PageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PageService $pageService
    ) {}

    /**
     * List all pages (paginated).
     *
     * GET /api/v1/pages
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $status  = $request->input('status');

        $pages = $this->pageService->listPages($perPage, $status);

        return $this->success(
            PageResource::collection($pages),
            'Pages retrieved successfully.',
            200,
            [
                'current_page' => $pages->currentPage(),
                'last_page'    => $pages->lastPage(),
                'per_page'     => $pages->perPage(),
                'total'        => $pages->total(),
            ]
        );
    }

    /**
     * Get a single page by its page number (with all components and assets).
     *
     * GET /api/v1/pages/{pageNumber}
     */
    public function show(int $pageNumber): JsonResponse
    {
        $page = $this->pageService->getPageByNumber($pageNumber);

        if (!$page) {
            return $this->notFound("Page #{$pageNumber} not found.");
        }

        return $this->success(
            new PageResource($page),
            'Page retrieved successfully.'
        );
    }

    /**
     * Create a new page.
     *
     * POST /api/v1/pages
     *
     * Body:
     *   page_number, title, slug?, meta_description?, status?,
     *   components?: ["CTA 2", "IMGGAL 3"]
     */
    public function store(StorePageRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Separate components from page data
        $componentIds = $data['components'] ?? null;
        unset($data['components']);

        // Set the creator
        $data['created_by'] = auth()->id();

        $page = $this->pageService->createPage($data, $componentIds);

        return $this->created(
            new PageResource($page),
            'Page created successfully.'
        );
    }

    /**
     * Update page metadata.
     *
     * PUT /api/v1/pages/{pageNumber}
     */
    public function update(UpdatePageRequest $request, int $pageNumber): JsonResponse
    {
        $page = $this->pageService->updatePage($pageNumber, $request->validated());

        if (!$page) {
            return $this->notFound("Page #{$pageNumber} not found.");
        }

        return $this->success(
            new PageResource($page),
            'Page updated successfully.'
        );
    }

    /**
     * Delete a page (soft-delete).
     *
     * DELETE /api/v1/pages/{pageNumber}
     */
    public function destroy(int $pageNumber): JsonResponse
    {
        $deleted = $this->pageService->deletePage($pageNumber);

        if (!$deleted) {
            return $this->notFound("Page #{$pageNumber} not found.");
        }

        return $this->success(null, 'Page deleted successfully.');
    }

    // -------------------------------------------------------
    // Page Components
    // -------------------------------------------------------

    /**
     * Attach components to a page.
     *
     * POST /api/v1/pages/{pageNumber}/components
     *
     * Body:
     *   components: [
     *     { component_id: "CTA 2", sort_order: 1, config_overrides: {...}, is_visible: true },
     *     { component_id: "IMGGAL 3", sort_order: 2 }
     *   ]
     */
    public function attachComponents(AttachComponentsRequest $request, int $pageNumber): JsonResponse
    {
        $page = $this->pageService->getPageByNumber($pageNumber);

        if (!$page) {
            return $this->notFound("Page #{$pageNumber} not found.");
        }

        $result = $this->pageService->attachComponents($page, $request->validated('components'));

        $response = [
            'attached' => PageComponentResource::collection(collect($result['attached'])),
        ];

        if (!empty($result['errors'])) {
            $response['errors'] = $result['errors'];
        }

        $message = count($result['attached']) . ' component(s) attached.';
        if (!empty($result['errors'])) {
            $message .= ' ' . count($result['errors']) . ' failed.';
        }

        return $this->success($response, $message, empty($result['errors']) ? 200 : 207);
    }

    /**
     * Update a specific page component (config, visibility, order).
     *
     * PUT /api/v1/pages/{pageNumber}/components/{pageComponentId}
     */
    public function updateComponent(Request $request, int $pageNumber, int $pageComponentId): JsonResponse
    {
        $data = $request->validate([
            'sort_order'       => ['sometimes', 'integer', 'min:0'],
            'config_overrides' => ['sometimes', 'nullable', 'array'],
            'is_visible'       => ['sometimes', 'boolean'],
        ]);

        $pc = $this->pageService->updatePageComponent($pageComponentId, $data);

        if (!$pc) {
            return $this->notFound('Page component not found.');
        }

        return $this->success(
            new PageComponentResource($pc),
            'Page component updated.'
        );
    }

    /**
     * Remove a component from a page.
     *
     * DELETE /api/v1/pages/{pageNumber}/components/{pageComponentId}
     */
    public function removeComponent(int $pageNumber, int $pageComponentId): JsonResponse
    {
        $deleted = $this->pageService->removeComponent($pageComponentId);

        if (!$deleted) {
            return $this->notFound('Page component not found.');
        }

        return $this->success(null, 'Component removed from page.');
    }

    /**
     * Bulk reorder components on a page.
     *
     * PATCH /api/v1/pages/{pageNumber}/components/reorder
     */
    public function reorderComponents(ReorderComponentsRequest $request, int $pageNumber): JsonResponse
    {
        $this->pageService->reorderComponents($request->validated('order'));

        // Return the refreshed page
        $page = $this->pageService->getPageByNumber($pageNumber);

        return $this->success(
            new PageResource($page),
            'Components reordered successfully.'
        );
    }
}
