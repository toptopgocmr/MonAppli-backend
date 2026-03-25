<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionRate;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    /**
     * Calcule et enregistre la commission d'une course terminée.
     * Appelé automatiquement par CourseObserver quand status → completed.
     *
     * @throws \Exception si la course n'est pas complétée ou si une commission existe déjà
     */
    public function calculer(Course $course): Commission
    {
        if (! $course->isCompleted()) {
            throw new \Exception("La commission ne peut être calculée que pour une course complétée. (course_id={$course->id})");
        }

        // Éviter les doublons
        if ($course->commission()->exists()) {
            Log::warning("Commission déjà existante pour course_id={$course->id}");
            return $course->commission;
        }

        return DB::transaction(function () use ($course) {
            // 1. Résoudre le taux applicable (ville > pays)
            $rateModel = CommissionRate::resolveRate($course->country_id, $course->city_id);

            if (! $rateModel) {
                // Taux par défaut si aucun taux configuré = 0%
                Log::warning("Aucun taux de commission trouvé pour country_id={$course->country_id} city_id={$course->city_id}. Taux 0% appliqué.");
                $taux = 0;
                $rateId = null;
            } else {
                $taux   = (float) $rateModel->rate;
                $rateId = $rateModel->id;
            }

            // 2. Calcul du montant
            $montantCommission = round($course->montant_total * $taux / 100, 2);

            // 3. Création de la commission
            $commission = Commission::create([
                'course_id'          => $course->id,
                'driver_id'          => $course->driver_id,
                'user_id'            => $course->user_id,
                'country_id'         => $course->country_id,
                'city_id'            => $course->city_id,
                'commission_rate_id' => $rateId,
                'montant_course'     => $course->montant_total,
                'taux_applique'      => $taux,
                'montant_commission' => $montantCommission,
                'currency'           => $course->currency,
                'earned_at'          => $course->completed_at ?? now(),
            ]);

            Log::info("Commission créée : course_id={$course->id} | montant={$course->montant_total} | taux={$taux}% | commission={$montantCommission}");

            return $commission;
        });
    }
}