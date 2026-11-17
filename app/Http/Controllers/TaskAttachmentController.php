<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TaskAttachmentController extends Controller
{
    /**
     * Initialize Cloudinary using Laravel configuration.
     */
    private function cloudinary(): UploadApi
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new RuntimeException(
                'Cloudinary credentials are not configured.'
            );
        }

        $configuration = new \Cloudinary\Configuration\Configuration([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $cloudinary = new \Cloudinary\Cloudinary($configuration);

        return $cloudinary->uploadApi();
    }

    /**
     * Upload an attachment to Cloudinary.
     */
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv',
            ],
        ]);

        $file = $request->file('file');

        try {
            $result = $this->cloudinary()->upload(
                $file->getRealPath(),
                [
                    'folder' => 'task-tracker/attachments',
                    'resource_type' => 'auto',
                ]
            );

            $publicId = $result['public_id'];
            $secureUrl = $result['secure_url'];

            $attachment = $task->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $secureUrl,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'cloudinary_public_id' => $publicId,
                'cloudinary_url' => $secureUrl,
            ]);

            return response()->json([
                'message' => 'Attachment uploaded successfully.',
                'attachment' => $attachment,
            ], 201);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to upload attachment. Please try again.',
            ], 500);
        }
    }

    /**
     * Get all attachments belonging to a task.
     */
    public function index(Task $task)
    {
        return response()->json(
            $task->attachments()->latest()->get()
        );
    }

    /**
     * Delete an attachment from Cloudinary or local storage.
     */
    public function destroy(TaskAttachment $attachment)
    {
        try {
            if ($attachment->cloudinary_public_id) {
                $resourceType = str_starts_with(
                    $attachment->mime_type ?? '',
                    'image/'
                ) ? 'image' : 'raw';

                $this->cloudinary()->destroy(
                    $attachment->cloudinary_public_id,
                    [
                        'resource_type' => $resourceType,
                    ]
                );

            } elseif ($attachment->file_path) {
                Storage::disk('public')->delete(
                    $attachment->file_path
                );
            }

            $attachment->delete();

            return response()->json([
                'message' => 'Attachment deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to delete attachment. Please try again.',
            ], 500);
        }
    }
}
