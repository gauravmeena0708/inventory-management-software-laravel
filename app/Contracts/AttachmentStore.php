<?php

namespace App\Contracts;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface AttachmentStore
{
    /**
     * Store an uploaded file and attach it to a model.
     */
    public function store(UploadedFile $file, Model $attachable, User $user, ?string $disk = 'private'): Attachment;

    /**
     * Retrieve an attachment file as a streamed download response.
     */
    public function retrieve(Attachment $attachment): StreamedResponse;

    /**
     * Delete an attachment record and its underlying stored file.
     */
    public function delete(Attachment $attachment): bool;
}
