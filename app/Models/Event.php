<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'user_id','slug','title','event_date','venue_name','venue_address','venue_map_url',
        'welcome_message','rsvp_deadline','status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'rsvp_deadline' => 'datetime',
    ];

    const STATUS_PUBLISHED = 'published';

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function guests(): HasMany { return $this->hasMany(Guest::class); }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function isRsvpClosed(): bool
    {
        return $this->rsvp_deadline !== null && now()->greaterThan($this->rsvp_deadline);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
