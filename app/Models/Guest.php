<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'status',
        'invite_status',
        'rsvp_status',
        'party_size',
        'phone',
        'email',
        'invite_code',
        'notes',
        'meal_choice',
        'check_in_status',
        'check_in_at',
        'seating_table_id',
        'seat_label',
    ];

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }

    public function seatingTable(): BelongsTo
    {
        return $this->belongsTo(SeatingTable::class);
    }

    public function seatingAssignment(): HasOne
    {
        return $this->hasOne(SeatingAssignment::class);
    }
}
