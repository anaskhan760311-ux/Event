@extends('layouts.app')

@section('title', 'Discover events')

@section('content')
    <section class="hero">
        <span class="eyebrow">Make room for something good</span>
        <h1>Find your next<br>favorite thing.</h1>
        <p>Good people, fresh ideas, and memorable nights. Find an event worth looking forward to.</p>
    </section>
    <div class="section-heading">
        <h2>Coming up</h2>
        <span class="count">{{ $events->count() }} events</span>
    </div>
    <section class="grid" aria-label="Upcoming events">
        @foreach ($events as $event)
            <a class="card" href="{{ route('events.show', $event) }}">
                <div class="card-art"><span class="badge">{{ $event->category }}</span></div>
                <div class="card-body">
                    <span class="date">{{ $event->event_date->format('F j, Y') }}</span>
                    <h3>{{ $event->name }}</h3>
                    <div class="venue">{{ $event->location }}</div>
                    <div class="card-bottom"><span class="price">₹{{ number_format((float) $event->price, 2) }}</span><span class="arrow" aria-hidden="true">↗</span></div>
                </div>
            </a>
        @endforeach
    </section>
@endsection
