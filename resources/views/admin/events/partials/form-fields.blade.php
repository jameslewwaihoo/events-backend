<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="event_type">Event Type</label>
            <select class="form-control" id="event_type" name="event_type" required>
                @foreach (['wedding' => 'Wedding'] as $value => $label)
                    <option value="{{ $value }}"
                        {{ old('event_type', $event->event_type) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="event_package_id">Package</label>
            <select class="form-control" id="event_package_id" name="event_package_id">
                <option value="">Select package</option>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}"
                        {{ (string) old('event_package_id', $event->event_package_id) === (string) $package->id ? 'selected' : '' }}>
                        {{ $package->name }} ({{ $package->code }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="form-group">
            <label for="user_id">Purchaser</label>
            <select class="form-control" id="user_id" name="user_id" required>
                @foreach ($purchasers as $purchaser)
                    <option value="{{ $purchaser->id }}"
                        {{ (string) old('user_id', $event->user_id ?? auth()->id()) === (string) $purchaser->id ? 'selected' : '' }}>
                        {{ $purchaser->name }} ({{ $purchaser->email }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-3 col-md-12">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text"
                   class="form-control"
                   id="title"
                   name="title"
                   value="{{ old('title', $event->title) }}"
                   required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text"
                   class="form-control"
                   id="slug"
                   name="slug"
                   value="{{ old('slug', $event->slug) }}"
                   required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="event_date">Event Date</label>
            <input type="text"
                   class="form-control js-datepicker"
                   id="event_date"
                   name="event_date"
                   autocomplete="off"
                   placeholder="YYYY-MM-DD"
                   value="{{ old('event_date', optional($event->event_date)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="rsvp_deadline">RSVP Deadline</label>
            <input type="text"
                   class="form-control js-datepicker"
                   id="rsvp_deadline"
                   name="rsvp_deadline"
                   autocomplete="off"
                   placeholder="YYYY-MM-DD"
                   value="{{ old('rsvp_deadline', optional($event->rsvp_deadline)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="col-lg-4 col-md-12">
        <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status" required>
                @foreach (['draft' => 'Draft', 'published' => 'Published'] as $value => $label)
                    <option value="{{ $value }}"
                        {{ old('status', $event->status) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="venue_name">Venue Name</label>
            <input type="text"
                   class="form-control"
                   id="venue_name"
                   name="venue_name"
                   value="{{ old('venue_name', $event->venue_name) }}">
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="form-group">
            <label for="venue_address">Venue Address</label>
            <input type="text"
                   class="form-control"
                   id="venue_address"
                   name="venue_address"
                   value="{{ old('venue_address', $event->venue_address) }}">
        </div>
    </div>
    <div class="col-lg-4 col-md-12">
        <div class="form-group">
            <label for="venue_map_url">Venue Map URL</label>
            <input type="url"
                   class="form-control"
                   id="venue_map_url"
                   name="venue_map_url"
                   value="{{ old('venue_map_url', $event->venue_map_url) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="form-group mb-0">
            <label for="welcome_message">Welcome Message</label>
            <textarea class="form-control"
                      id="welcome_message"
                      name="welcome_message"
                      rows="4">{{ old('welcome_message', $event->welcome_message) }}</textarea>
        </div>
    </div>
</div>
