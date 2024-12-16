<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use App\Http\Requests\Products\StoreProductRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait StoreImageTrait
{
    /**
        * Handles the storage of an uploaded image.
        * - Checks if a file is uploaded for the given field name.
        * - If an old image path is provided, deletes the old image from the storage disk.
        * - Stores the new image in the specified directory and disk.
        * - Returns the path of the newly uploaded image, or the old image path if no new image is uploaded.
        * - 
     *
     * @param Request $request
     * @param string $fieldName
     * @param string $directory
     * @param string $disk
     * @return string|null
     */

    public function storeImage($request, string $fieldName = 'image', string $directory = 'uploads', string $disk = 'public', ?string $oldImagePath = null): ?string
    {
        if ($request->hasFile($fieldName)) {
            try {
                // Delete old image if provided
                if ($oldImagePath && Storage::disk($disk)->exists($oldImagePath)) {
                    Storage::disk($disk)->delete($oldImagePath);
                }

                // Store the new image
                return $request->file($fieldName)->store($directory, $disk);
            } catch (\Throwable $th) {
                abort(500, 'Failed to upload image.');
            }
        }

        // Return the old image path if no new image is uploaded
        return $oldImagePath;
    }

}
