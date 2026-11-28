@extends('layouts.app')

@section('title', $event->name)

@section('content')
    <section class="detail">
        <a class="back" href="{{ route('events.index') }}">← &nbsp; All events</a>
        @if (session('status'))
            <p class="cta">{{ session('status') }}</p>
        @endif
        <div><span class="eyebrow">{{ $event->category }}</span></div>
        <h1>{{ $event->name }}</h1>
        <p class="detail-intro">{{ $event->description }}</p>
        <div class="detail-panel">
            <div><div class="detail-label">Date</div><div class="detail-value">{{ $event->event_date->format('F j, Y') }}</div></div>
            <div><div class="detail-label">Location</div><div class="detail-value">{{ $event->location }}</div></div>
            <div><div class="detail-label">Admission</div><div class="detail-value detail-price">₹{{ number_format((float) $event->price, 2) }}</div></div>
        </div>
        <p class="description">{{ $event->description }}</p>
        <a class="cta" href="{{ route('events.edit', $event) }}">Edit event</a>
        <form method="POST" action="{{ route('events.destroy', $event) }}" style="display:inline" onsubmit="return confirm('Delete this event?')">
            @csrf
            @method('DELETE')
            <button class="cta" type="submit">Delete event</button>
        </form>
        <a class="cta" href="{{ route('events.index') }}">Back to events</a>
    </section>
@endsection
