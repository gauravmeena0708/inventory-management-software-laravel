<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\SpatialMap;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttachmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        return ($attachable instanceof Asset
                || $attachable instanceof Location
                || $attachable instanceof FileRecord
                || $attachable instanceof Agreement
                || $attachable instanceof Task
                || $attachable instanceof SpatialMap)
            && $user->can('view', $attachable);
    }
}
