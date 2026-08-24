<?php

namespace App\Services\Storage;

use App\Contracts\AttachmentStore;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDiskAttachmentStore implements AttachmentStore
{
    /**
     * Store an uploaded file to the configured disk and record attachment metadata.
     */
    public function store(UploadedFile $file, Model $attachable, User $user, ?string $disk = 'private'): Attachment
    {
        $targetDisk = $disk ?: 'private';

        $realPath = $file->getRealPath();
        $checksum = ($realPath && file_exists($realPath))
            ? hash_file('sha256', $realPath)
            : hash('sha256', $file->getContent());

        $mimeType = $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream';
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize() ?: 0;

        $folder = 'attachments/' . date('Y/m');
        $storedPath = $file->store($folder, $targetDisk);

        if (!$storedPath) {
            throw new RuntimeException('Failed to store attachment file to disk.');
        }

        $attachment = new Attachment([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'disk' => $targetDisk,
            'path' => $storedPath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'checksum' => $checksum,
            'uploaded_by' => $user->getKey(),
        ]);

        $attachment->save();

        return $attachment;
    }

    /**
     * Retrieve an attachment file as a streamed download response.
     */
    public function retrieve(Attachment $attachment): StreamedResponse
    {
        $diskName = $attachment->disk ?: 'private';
        $disk = Storage::disk($diskName);

        if (!$disk->exists($attachment->path)) {
            abort(404, 'Attachment file not found on storage disk.');
        }

        return $disk->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
            ]
        );
    }

    /**
     * Delete an attachment record and its underlying stored file.
     */
    public function delete(Attachment $attachment): bool
    {
        $diskName = $attachment->disk ?: 'private';
        $disk = Storage::disk($diskName);

        if ($disk->exists($attachment->path)) {
            $disk->delete($attachment->path);
        }

        if ($attachment->exists) {
            return (bool) $attachment->delete();
        }

        return true;
    }
}
