<?php
// Crear Job: php artisan make:job GenerateGroupResultsJob

namespace App\Jobs;

use App\Models\Group;
use App\Models\Result;
use Carbon\Carbon;

class GenerateGroupResultsJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public $groupId;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    public function handle()
    {
        $group = Group::with('evaluation', 'students')->find($this->groupId);
        if (!$group) return;

        $evaluation = $group->evaluation;
        $results = [];
        $allScores = [];

        foreach ($group->students as $student) {
            $studentTest = $student->studentTests()->where('evaluation_id', $evaluation->id)->first();
            if (!$studentTest) continue;

            // Si no completó, marcar como no se presentó
            if ($studentTest->status !== 'completado') {
                $studentTest->update([
                    'status' => 'completado',
                    'score_obtained' => 0,
                    'end_time' => $studentTest->start_time ?? now(),
                ]);

                $score = 0;
                $examDuration = '00:00:00';
                $status = 'no_se_presento';
            } else {
                $score = $studentTest->score_obtained;

                $start = Carbon::parse($studentTest->start_time);
                $end = Carbon::parse($studentTest->end_time);
                $examDuration = $start->diff($end)->format('%H:%I:%S');

                $status = $score >= $evaluation->passing_score ? 'admitido' : 'no_admitido';
            }

            // Guardar o actualizar result
            Result::updateOrCreate(
                ['student_test_id' => $studentTest->id],
                [
                    'qualification' => $score,
                    'exam_duration' => $examDuration,
                    'status' => $status,
                ]
            );

            $allScores[] = $score;

            $results[] = [
                'student_name' => $student->name,
                'student_ci' => $student->ci,
                'score_obtained' => $score,
                'exam_duration' => $examDuration,
                'status' => $status,
            ];
        }

        // Actualizar máximo y mínimo de los resultados de este grupo
        if (!empty($allScores)) {
            $maximumScore = max($allScores);
            $minimumScore = min($allScores);

            Result::whereIn('student_test_id', $group->students->pluck('student_test_ids')->flatten())
                ->update([
                    'maximum_score' => $maximumScore,
                    'minimum_score' => $minimumScore,
                ]);
        }
    }
}
