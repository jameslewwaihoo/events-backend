<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->user_id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    public function manageAssets(User $user, Event $event, ?string $type = null): bool
    {
        if (!$this->update($user, $event)) {
            return false;
        }

        if ($type === 'video') {
            return $event->featureEnabled('video_enabled');
        }

        return $event->featureEnabled('photos_enabled');
    }

    public function manageSessions(User $user, Event $event): bool
    {
        return $this->update($user, $event) && $event->featureEnabled('sessions_enabled');
    }

    public function manageGuests(User $user, Event $event): bool
    {
        return $this->update($user, $event) && $event->featureEnabled('rsvp_enabled');
    }

    public function manageInvites(User $user, Event $event): bool
    {
        return $this->update($user, $event) && $event->featureEnabled('email_invites_enabled');
    }

    public function manageAttendance(User $user, Event $event): bool
    {
        return $this->update($user, $event) && $event->featureEnabled('attendance_enabled');
    }

    public function manageMeals(User $user, Event $event): bool
    {
        return $this->update($user, $event) && $event->featureEnabled('meals_enabled');
    }

    public function manageSeating(User $user, Event $event): bool
    {
        return $this->update($user, $event) && $event->featureEnabled('seating_enabled');
    }
}
