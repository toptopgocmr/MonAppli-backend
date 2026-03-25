<?php

namespace App\Observers;

use App\Models\Course;
use App\Services\CommissionService;
use Illuminate\Support\Facades\Log;

class CourseObserver
{
    public function __construct(protected CommissionService $commissionService) {}

    /**
     * Déclenche le calcul de commission automatiquement
     * quand le statut d'une course passe à "completed".
     */
    public function updated(Course $course): void
    {
        // Vérifier que le statut vient de passer à "completed"
        if (
            $course->isDirty('status') &&
            $course->status === 'completed' &&
            $course->getOriginal('status') !== 'completed'
        ) {
            try {
                $this->commissionService->calculer($course);
            } catch (\Exception $e) {
                Log::error("Erreur calcul commission course_id={$course->id} : " . $e->getMessage());
            }
        }
    }
}