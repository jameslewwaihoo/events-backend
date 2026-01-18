<?php

namespace App\Policies;

use App\Models\EventAsset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventAssetPolicy
{
    use HandlesAuthorization;

    public function view(User $user, EventAsset $asset): bool
    {
        return $user->isAdmin() || $asset->event && $asset->event->user_id === $user->id;
    }

    public function create(User $user, EventAsset $asset): bool
    {
        $event = $asset->event;
        if (!$event) {
            return false;
        }

        $type = $asset->type;
        if ($type === 'video') {
            return $event->featureEnabled('video_enabled') && ($user->isAdmin() || $event->user_id === $user->id);
        }

        return $event->featureEnabled('photos_enabled') && ($user->isAdmin() || $event->user_id === $user->id);
    }

    public function update(User $user, EventAsset $asset): bool
    {
        return $this->create($user, $asset);
    }

    public function delete(User $user, EventAsset $asset): bool
    {
        return $this->create($user, $asset);
    }
}
