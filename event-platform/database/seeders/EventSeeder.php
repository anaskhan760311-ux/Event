<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            ['name' => 'Summer Music Festival', 'description' => 'Spend a summer evening outdoors with live performances, local food, and a full festival lineup.', 'category' => 'Music', 'event_date' => '2027-06-14', 'location' => 'Riverside Park, Austin', 'price' => 4500],
            ['name' => 'Design Forward Conference', 'description' => 'A day of practical talks and workshops for people shaping the next generation of digital products.', 'category' => 'Technology', 'event_date' => '2027-07-08', 'location' => 'The Contemporary, Chicago', 'price' => 1120],
            ['name' => 'Farm to Table Evening', 'description' => 'Share a seasonal multi-course dinner made with ingredients harvested from the surrounding farm.', 'category' => 'Food & Drink', 'event_date' => '2027-08-02', 'location' => 'Willow Creek Farm, Portland', 'price' => 778],
            ['name' => 'City 5K for All', 'description' => 'Run, walk, or cheer along a welcoming waterfront route supporting neighborhood programs.', 'category' => 'Community', 'event_date' => '2027-08-21', 'location' => 'Harborfront Trail, Seattle', 'price' => 2225],
            ['name' => 'Independent Film Weekend', 'description' => 'Discover independent films and meet the filmmakers during a weekend of screenings and conversations.', 'category' => 'Arts & Culture', 'event_date' => '2027-09-10', 'location' => 'Landmark Cinema, Denver', 'price' => 3332],
        ];

        foreach ($events as $event) {
            Event::firstOrCreate(['name' => $event['name']], $event);
        }
    }
}
