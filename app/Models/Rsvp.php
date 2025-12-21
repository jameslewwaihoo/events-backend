<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rsvp extends Model
{
    protected $fillable = [
        'event_id','guest_id','session_id',
        'name','phone','email','attendance_status','remarks','submitted_at',
    ];

    protected $casts = ['submitted_at' => 'datetime'];

    const   STATUS_ATTENDING    = 'attending',
            STATUS_DECLINED     = 'declined';


    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function guest(): BelongsTo { return $this->belongsTo(Guest::class); }
    public function session(): BelongsTo { return $this->belongsTo(EventSession::class, 'session_id'); }
}
