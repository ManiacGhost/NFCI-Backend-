<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\UploadAssetRequest;
use App\Http\Resources\ComponentAssetResource;
use App\Services\AssetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AssetService $assetService
    ) {}

    /**
     * Upload assets to a page component.
     *
     * POST /api/v1/assets/upload
     *
     * Body (multipart/form-data):
     *   page_component_id, files[], alt_texts[]?, asset_type?
     */
    public function upload(UploadAssetRequest $request): JsonResponse
    {
        $result = $this->assetService->uploadAssets(
            $request->validated('page_component_id'),
            $request->file('files'),
            $request->input('alt_texts'),
            $request->input('asset_type', 'image')
        );

        $response = [
            'uploaded' => ComponentAssetResource::collection(collect($result['uploaded'])),
        ];

        if (!empty($result['errors'])) {
            $response['errors'] = $result['errors'];
        }

        $message = count($result['uploaded']) . ' asset(s) uploaded.';
        if (!empty($result['errors'])) {
            $message .= ' ' . count($result['errors']) . ' failed.';
        }

        return $this->success($response, $message, empty($result['errors']) ? 201 : 207);
    }

    /**
     * Delete an asset.
     *
     * DELETE /api/v1/assets/{assetId}
     */
    public function destroy(int $assetId): JsonResponse
    {
        $deleted = $this->assetService->deleteAsset($assetId);

        if (!$deleted) {
            return $this->notFound('Asset not found.');
        }

        return $this->success(null, 'Asset deleted successfully.');
    }
}
