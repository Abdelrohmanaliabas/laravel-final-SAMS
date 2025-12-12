<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AssessmentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $faker = fake();

        foreach (Group::with('lessons')->get() as $group) {
            $lessons = $group->lessons;
            if ($lessons->isEmpty()) {
                continue;
            }

            foreach (range(1, SeedBlueprints::assessmentsPerGroup()) as $index) {
                $lesson = $lessons->random();
                $template = $this->pickTemplate();
                $scheduledAt = $lesson->scheduled_at
                    ? $lesson->scheduled_at->copy()->addDays($faker->numberBetween(1, 3))
                    : Carbon::now()->addDays($faker->numberBetween(1, 3));

                Assessment::create([
                    'center_id' => $group->center_id,
                    'group_id' => $group->id,
                    'lesson_id' => $lesson->id,
                    'title' => "{$template} {$index}",
                    'max_score' => 100,
                    'scheduled_at' => $scheduledAt,
                ]);
            }
        }
    }

    private function pickTemplate(): string
    {
        $templates = SeedBlueprints::assessmentTemplates();
        return $templates[array_rand($templates)];
    }
}
