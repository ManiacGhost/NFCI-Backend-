<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Services\PageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public endpoint for frontend page rendering.
 * No authentication required.
 */
class PublicPageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PageService $pageService
    ) {}

    /**
     * Render a page by its page number.
     * Returns the page with all visible components, their configs, and assets.
     *
     * GET /api/v1/render/{pageNumber}
     */
    public function render(int $pageNumber): JsonResponse
    {
        $page = $this->pageService->getPageByNumber($pageNumber);

        if (!$page || $page->status !== 'active') {
            return $this->notFound("Page #{$pageNumber} is not available.");
        }

        // Filter to only visible components
        $page->setRelation(
            'pageComponents',
            $page->pageComponents->where('is_visible', true)->values()
        );

        return $this->success(
            new PageResource($page),
            'Page rendered successfully.'
        );
    }
}
