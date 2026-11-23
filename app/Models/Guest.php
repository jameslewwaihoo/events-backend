<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    protected $hidden = ['invitation_token'];

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

    public function scopeInvited(Builder $query): Builder
    {
        return $query->where(function (Builder $guest) {
            $guest->whereNotNull('invitation_token')->orWhereNotNull('email')->orWhereNotNull('phone');
        });
    }

    public static function normalizedRsvpPhone(?string $phone): ?string
    {
        $number = preg_replace('/[\s().-]+/u', '', $phone ?? '');
        $number = preg_replace('/\A(?:\+|00)/', '', $number);

        return preg_match('/\A[0-9]{6,15}\z/', $number) ? $number : null;
    }

    public static function normalizedRsvpMobile(?string $phone, ?string $country = null): ?string
    {
        $number = preg_replace('/[\s().-]+/u', '', $phone ?? '') ?? '';

        if (($country === null || $country === '60')
            && preg_match('/\A(?:0|(?:\+|00)?60)?(1(?:[0234678][0-9]{7}|9[0-9]{7,8}|1[0-9]{8}|5(?:31[0-9]{6}|9[0-9]{7})))\z/', $number, $match)) {
            return '60'.$match[1];
        }

        if (($country === null || $country === '65')
            && preg_match('/\A(?:(?:\+|00)?65)?([89][0-9]{7})\z/', $number, $match)) {
            return '65'.$match[1];
        }

        return null;
    }

    public function householdReply(): HasOne
    {
        return $this->hasOne(Rsvp::class)->where('household_reply', true);
    }

    public function issueInvitation(): void
    {
        if (!$this->invitation_token) {
            $this->invitation_token = bin2hex(random_bytes(32));
        }
    }

    public function seatingTable(): BelongsTo
    {
        return $this->belongsTo(SeatingTable::class);
    }

    public function seatingAssignment(): HasOne
    {
        return $this->hasOne(SeatingAssignment::class);
    }
}
