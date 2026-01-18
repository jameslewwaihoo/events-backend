<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GuestPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Guest $guest): bool
    {
        $event = $guest->event;
        return $user->isAdmin() || ($event && $event->user_id === $user->id);
    }

    public function create(User $user, Guest $guest): bool
    {
        $event = $guest->event;
        return $event && $event->featureEnabled('rsvp_enabled') && ($user->isAdmin() || $event->user_id === $user->id);
    }

    public function update(User $user, Guest $guest): bool
    {
        return $this->create($user, $guest);
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $user->isAdmin();
    }
}
