@extends('layouts.app')

@section('title', 'Edit '.$event->name)

@section('content')
    <section class="hero">
        <span class="eyebrow">Update event details</span>
        <h1>Edit event.</h1>
    </section>

    <form class="event-form" method="POST" action="{{ route('events.update', $event) }}">
        @csrf
        @method('PUT')
        @include('events.form-fields')
        <div class="form-field full">
            <button class="cta" type="submit">Save changes</button>
        </div>
    </form>
@endsection
