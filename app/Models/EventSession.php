<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    protected $table = 'event_sessions';

    protected $fillable = ['event_id','name','start_at','location','sort_order'];

    protected $casts = ['start_at' => 'datetime'];

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
