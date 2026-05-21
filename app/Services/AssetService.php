<?php

namespace App\Services;

use App\Models\ComponentAsset;
use App\Models\PageComponent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssetService
{
    /**
     * Upload multiple files to a page component.
     *
     * @param  int                     $pageComponentId
     * @param  array<UploadedFile>     $files
     * @param  array<string>|null      $altTexts
     * @param  string                  $assetType
     * @return array{uploaded: array, errors: array}
     */
    public function uploadAssets(
        int     $pageComponentId,
        array   $files,
        ?array  $altTexts = null,
        string  $assetType = 'image'
    ): array {
        $uploaded = [];
        $errors   = [];

        $pc = PageComponent::find($pageComponentId);

        if (!$pc) {
            return [
                'uploaded' => [],
                'errors'   => [['error' => 'Page component not found.']],
            ];
        }

        $maxSortOrder = $pc->assets()->max('sort_order') ?? 0;

        DB::transaction(function () use ($pc, $files, $altTexts, $assetType, &$uploaded, &$errors, &$maxSortOrder) {
            foreach ($files as $index => $file) {
                try {
                    // Determine asset type from MIME if not explicitly set
                    $detectedType = $this->detectAssetType($file, $assetType);

                    // Store file: assets/page_{pageId}/component_{pcId}/
                    $directory = "assets/page_{$pc->page_id}/component_{$pc->id}";
                    $path = $file->store($directory, 'public');

                    $maxSortOrder++;

                    $asset = ComponentAsset::create([
                        'page_component_id' => $pc->id,
                        'asset_type'        => $detectedType,
                        'file_path'         => $path,
                        'original_name'     => $file->getClientOriginalName(),
                        'alt_text'          => $altTexts[$index] ?? null,
                        'mime_type'         => $file->getMimeType(),
                        'file_size'         => $file->getSize(),
                        'sort_order'        => $maxSortOrder,
                    ]);

                    $uploaded[] = $asset;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'index' => $index,
                        'file'  => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return compact('uploaded', 'errors');
    }

    /**
     * Delete a single asset.
     */
    public function deleteAsset(int $assetId): bool
    {
        $asset = ComponentAsset::find($assetId);

        if (!$asset) {
            return false;
        }

        // Remove file from storage
        if (Storage::disk('public')->exists($asset->file_path)) {
            Storage::disk('public')->delete($asset->file_path);
        }

        return (bool) $asset->delete();
    }

    /**
     * Detect asset type from the uploaded file's MIME type.
     */
    private function detectAssetType(UploadedFile $file, string $default): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return str_contains($mime, 'svg') ? 'icon' : 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return $default;
    }
}
