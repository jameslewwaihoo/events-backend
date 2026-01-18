<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventPackage extends Model
{
    protected $fillable = [
        'name',
        'code',
        'price_cents',
        'rsvp_enabled',
        'photos_enabled',
        'video_enabled',
        'sessions_enabled',
        'email_invites_enabled',
        'attendance_enabled',
        'meals_enabled',
        'seating_enabled',
        'max_guests',
        'is_active',
    ];

    protected $casts = [
        'rsvp_enabled' => 'boolean',
        'photos_enabled' => 'boolean',
        'video_enabled' => 'boolean',
        'sessions_enabled' => 'boolean',
        'email_invites_enabled' => 'boolean',
        'attendance_enabled' => 'boolean',
        'meals_enabled' => 'boolean',
        'seating_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
