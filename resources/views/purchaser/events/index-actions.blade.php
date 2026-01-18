<div class="btn-group btn-group-sm" role="group">
    <a class="btn btn-outline-secondary" href="{{ route('purchaser.events.edit', $event->id) }}">Edit</a>
    <a class="btn btn-outline-primary" href="{{ route('purchaser.guests.index', $event->id) }}">Guests</a>
    <a class="btn btn-outline-primary" href="{{ route('purchaser.rsvps.index', $event->id) }}">RSVPs</a>
</div>
