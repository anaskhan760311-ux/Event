@if ($errors->any())
    <div class="form-errors" role="alert">
        <strong>Please correct the following:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="form-field">
    <label for="name">Event name</label>
    <input id="name" name="name" value="{{ old('name', $event->name) }}" maxlength="120" required>
</div>
<div class="form-field">
    <label for="category">Category</label>
    <input id="category" name="category" value="{{ old('category', $event->category) }}" maxlength="60" placeholder="Music, Arts, Community…" required>
</div>
<div class="form-field">
    <label for="event_date">Date</label>
    <input id="event_date" type="date" name="event_date" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" required>
</div>
<div class="form-field">
    <label for="location">Location</label>
    <input id="location" name="location" value="{{ old('location', $event->location) }}" maxlength="160" required>
</div>
<div class="form-field">
    <label for="price">Ticket price (₹)</label>
    <input id="price" type="number" name="price" value="{{ old('price', $event->price) }}" min="0" max="99999999.99" step="0.01" required>
</div>
<div class="form-field full">
    <label for="description">Description</label>
    <textarea id="description" name="description" maxlength="2000" required>{{ old('description', $event->description) }}</textarea>
</div>
