@extends('layouts.app')

@section('title', 'Add an event')

@section('content')
    <section class="hero">
        <span class="eyebrow">Share something worth showing up for</span>
        <h1>Add an event.</h1>
        <p>Enter the event details. It will be saved and shown in the events list.</p>
    </section>

    <form class="event-form" method="POST" action="{{ route('events.store') }}">
        @csrf
        @include('events.form-fields')
        <div class="form-field full">
            <button class="cta" type="submit">Create event</button>
        </div>
    </form>
@endsection
