@extends('layouts.app')

@section('title', 'Event submitted')

@section('content')
    <section class="hero">
        <span class="eyebrow">Event received</span>
        <h1>{{ $event['title'] }}</h1>
        <p>Your submitted event details are shown below.</p>
    </section>
    <section class="submitted-card">
        <div class="detail-panel" style="margin-top:0">
            <div><div class="detail-label">Category</div><div class="detail-value">{{ $event['category'] }}</div></div>
            <div><div class="detail-label">Admission</div><div class="detail-value detail-price">₹{{ number_format((float) $event['price'], 2) }}</div></div>
            <div><div class="detail-label">Date</div><div class="detail-value">{{ \Illuminate\Support\Carbon::parse($event['date'])->format('F j, Y') }}</div></div>
            <div><div class="detail-label">Time</div><div class="detail-value">{{ \Illuminate\Support\Carbon::createFromFormat('H:i', $event['time'])->format('g:i A') }}</div></div>
            <div class="full"><div class="detail-label">Venue</div><div class="detail-value">{{ $event['venue'] }}</div></div>
        </div>
        <p class="description">{{ $event['description'] }}</p>
        <a class="cta" href="{{ route('events.create') }}">Add another event</a>
        <a class="cta" href="{{ route('events.index') }}">Back to events</a>
    </section>
@endsection
