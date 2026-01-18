<?php

namespace App\Policies;

use App\Models\EventSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventSessionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, EventSession $session): bool
    {
        $event = $session->event;
        return $user->isAdmin() || ($event && $event->user_id === $user->id);
    }

    public function create(User $user, EventSession $session): bool
    {
        $event = $session->event;
        return $event && $event->featureEnabled('sessions_enabled') && ($user->isAdmin() || $event->user_id === $user->id);
    }

    public function update(User $user, EventSession $session): bool
    {
        return $this->create($user, $session);
    }

    public function delete(User $user, EventSession $session): bool
    {
        return $this->create($user, $session);
    }
}
