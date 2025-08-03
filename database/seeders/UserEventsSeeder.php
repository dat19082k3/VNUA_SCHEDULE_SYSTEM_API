<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class UserEventsSeeder extends Seeder
{
    public function run()
    {
        // Get all users and events
        $users = User::all();
        $events = Event::all();

        // Clear existing relationships to prevent duplicates
        DB::table('user_events')->truncate();

        foreach ($events as $event) {
            // Randomly mark events for some users (between 3-10 users per event)
            $usersToMark = $users->random(rand(3, min(10, $users->count())));

            foreach ($usersToMark as $user) {
                $isViewed = (bool)rand(0, 1); // Randomly decide if the event has been viewed

                DB::table('user_events')->insert([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'is_marked' => true,
                    'is_viewed' => $isViewed,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
