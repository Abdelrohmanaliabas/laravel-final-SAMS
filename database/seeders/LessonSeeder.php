<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LessonSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (Group::all() as $group) {
            foreach (range(1, SeedBlueprints::lessonsPerGroup()) as $lessonIndex) {
                $scheduledAt = Carbon::now()->subDays($lessonIndex * 3);

                Lesson::create([
                    'group_id' => $group->id,
                    'title' => "{$group->subject} Session {$lessonIndex}",
                    'description' => "Lesson {$lessonIndex} explores key {$group->subject} topics.",
                    'scheduled_at' => $scheduledAt,
                    'video_url' => "https://videos.sams.com/{$group->id}/session-{$lessonIndex}",
                ]);
            }
        }
    }
}
