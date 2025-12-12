<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\GroupStudent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $faker = fake();
        $statuses = SeedBlueprints::attendanceStatuses();

        foreach (Group::with('lessons')->get() as $group) {
            $lessonIds = $group->lessons->pluck('id')->all();
            if (empty($lessonIds)) {
                continue;
            }

            $assistants = User::role('assistant')
                ->where('center_id', $group->center_id)
                ->pluck('id')
                ->all();

            $staffPool = collect(array_merge([$group->teacher_id], $assistants))
                ->filter()
                ->values();

            if ($staffPool->isEmpty()) {
                continue;
            }

            $members = GroupStudent::where('group_id', $group->id)->get();

            foreach ($members as $membership) {
                $attendanceCount = $faker->numberBetween(2, 5);
                foreach (range(1, $attendanceCount) as $_) {
                    Attendance::firstOrCreate([
                        'group_id' => $group->id,
                        'student_id' => $membership->student_id,
                        'date' => Carbon::now()
                            ->subDays($faker->numberBetween(3, 21))
                            ->toDateString(),
                    ], [
                        'center_id' => $group->center_id,
                        'lesson_id' => $faker->randomElement($lessonIds),
                        'status' => $faker->randomElement($statuses),
                        'marked_by' => $staffPool->random(),
                    ]);
                }
            }
        }
    }
}
