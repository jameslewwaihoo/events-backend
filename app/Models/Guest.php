<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = ['event_id','name','phone','email','invite_code','notes'];

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
