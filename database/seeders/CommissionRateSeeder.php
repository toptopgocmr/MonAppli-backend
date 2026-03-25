<?php

namespace Database\Seeders;

use App\Models\CommissionRate;
use Illuminate\Database\Seeder;

class CommissionRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            // ── Taux par pays (fallback si pas de taux ville) ──────────────
            [
                'country_id' => 1,
                'city_id'    => null,
                'rate'       => 15.00,
                'is_active'  => true,
                'note'       => 'Taux général Congo (Brazzaville)',
            ],
            [
                'country_id' => 2,
                'city_id'    => null,
                'rate'       => 12.00,
                'is_active'  => true,
                'note'       => 'Taux général Cameroun',
            ],
            [
                'country_id' => 3,
                'city_id'    => null,
                'rate'       => 13.00,
                'is_active'  => true,
                'note'       => "Taux général Côte d'Ivoire",
            ],
            [
                'country_id' => 4,
                'city_id'    => null,
                'rate'       => 14.00,
                'is_active'  => true,
                'note'       => 'Taux général Gabon',
            ],

            // ── Taux spécifiques par ville (priorité sur le pays) ──────────
            [
                'country_id' => 1,
                'city_id'    => 1,
                'rate'       => 18.00,
                'is_active'  => true,
                'note'       => 'Brazzaville (forte demande)',
            ],
            [
                'country_id' => 1,
                'city_id'    => 2,
                'rate'       => 16.00,
                'is_active'  => true,
                'note'       => 'Pointe-Noire',
            ],
            [
                'country_id' => 2,
                'city_id'    => 3,
                'rate'       => 14.00,
                'is_active'  => true,
                'note'       => 'Douala',
            ],
            [
                'country_id' => 4,
                'city_id'    => 4,
                'rate'       => 15.00,
                'is_active'  => true,
                'note'       => 'Libreville',
            ],
            [
                'country_id' => 3,
                'city_id'    => 5,
                'rate'       => 13.50,
                'is_active'  => true,
                'note'       => 'Abidjan',
            ],
        ];

        foreach ($rates as $rate) {
            CommissionRate::updateOrCreate(
                [
                    'country_id' => $rate['country_id'],
                    'city_id'    => $rate['city_id'],
                ],
                $rate
            );
        }

        $this->command->info('✅ Taux de commission seedés avec succès !');
    }
}