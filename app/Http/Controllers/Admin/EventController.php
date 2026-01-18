<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $q = trim((string) $request->get('q', ''));

        $events = Event::query()
            ->with(['package', 'user'])
            ->when($q, function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('slug', 'like', "%{$q}%");
            })
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.events.index', [
            'events' => $events,
            'q'      => $q,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Event::class);

        return view('admin.events.create', [
            'event' => new Event([
                'status' => 'draft',
                'event_type' => 'wedding',
                'user_id' => auth()->id(),
            ]),
            'packages' => EventPackage::where('is_active', true)->orderBy('price_cents')->get(),
            'purchasers' => User::where('role', 'purchaser')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $data = $this->validateEvent($request);
        $data['user_id'] = $data['user_id'] ?? $request->user()->id;

        $event = Event::create($data);

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Event \"{$event->title}\" created.");
    }

    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return view('admin.events.edit', [
            'event' => $event,
            'packages' => EventPackage::where('is_active', true)->orderBy('price_cents')->get(),
            'purchasers' => User::where('role', 'purchaser')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $this->validateEvent($request, $event);
        $event->fill($data);
        $event->save();

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Event \"{$event->title}\" updated.");
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $title = $event->title;
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Event \"{$title}\" deleted.");
    }

    private function validateEvent(Request $request, ?Event $event = null): array
    {
        $eventId = $event?->id;

        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'event_type' => ['required', 'string', 'max:50'],
            'event_package_id' => ['nullable', 'exists:event_packages,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('events', 'slug')->ignore($eventId),
            ],
            'event_date' => ['nullable', 'date'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'venue_map_url' => ['nullable', 'url', 'max:255'],
            'welcome_message' => ['nullable', 'string'],
            'rsvp_deadline' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);
    }
}
