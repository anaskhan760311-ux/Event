<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('events.index', [
            'events' => Event::query()->orderBy('event_date')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('events.create', ['event' => new Event]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->validatedEvent($request));

        return redirect()->route('events.show', $event)->with('status', 'Event created successfully.');
    }

    public function show(Event $event): View
    {
        return view('events.show', ['event' => $event]);
    }

    public function edit(Event $event): View
    {
        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $event->update($this->validatedEvent($request));

        return redirect()->route('events.show', $event)->with('status', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('events.index')->with('status', 'Event deleted successfully.');
    }

    /** @return array{name: string, description: string, category: string, event_date: string, location: string, price: string} */
    private function validatedEvent(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:60'],
            'event_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:160'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);
    }
}
