<?php

namespace App\Http\Controllers;

use App\Contracts\AttachmentStore;
use App\Models\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(
        Attachment $attachment,
        AttachmentStore $store
    ): StreamedResponse {
        $this->authorize('view', $attachment);

        return $store->retrieve($attachment);
    }
}
