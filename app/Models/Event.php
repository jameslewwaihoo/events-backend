<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_package_id',
        'event_type',
        'slug',
        'title',
        'event_date',
        'venue_name',
        'venue_address',
        'venue_map_url',
        'welcome_message',
        'rsvp_deadline',
        'status',
        'published_at',
        'feature_overrides',
    ];

    protected $casts = [
        'event_date' => 'date',
        'rsvp_deadline' => 'datetime',
        'published_at' => 'datetime',
        'feature_overrides' => 'array',
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

    public function assets(): HasMany
    {
        return $this->hasMany(EventAsset::class);
    }

    public function seatingTables(): HasMany
    {
        return $this->hasMany(SeatingTable::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(EventPackage::class, 'event_package_id');
    }

    /**
     * Determine if a feature is enabled for this event, honoring package flags and overrides.
     */
    public function featureEnabled(string $key): bool
    {
        $overrides = $this->feature_overrides ?? [];
        if (array_key_exists($key, $overrides)) {
            return (bool) $overrides[$key];
        }

        $package = $this->package;
        if ($package && isset($package->{$key})) {
            return (bool) $package->{$key};
        }

        return false;
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
