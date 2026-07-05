<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\VehicleDriverShift;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    // Planning chauffeurs : vue récurrente (semaine type) + créneaux à dates précises à venir
    public function index(Request $request)
    {
        $company = $this->company();

        $base = VehicleDriverShift::whereHas('vehicle', fn($q) => $q->where('company_id', $company->id))
                    ->where('status', 'active')
                    ->with(['driver', 'vehicle']);

        // Créneaux récurrents groupés par jour (0=Dimanche ... 6=Samedi)
        $recurring = (clone $base)->whereNotNull('day_of_week')->get()->groupBy('day_of_week');

        // Créneaux à date précise, à venir (30 prochains jours)
        $upcoming = (clone $base)->whereNotNull('specific_date')
                    ->whereDate('specific_date', '>=', now()->toDateString())
                    ->whereDate('specific_date', '<=', now()->addDays(30)->toDateString())
                    ->orderBy('specific_date')
                    ->get()
                    ->groupBy(fn($s) => $s->specific_date->format('Y-m-d'));

        $days = \App\Models\VehicleDriverShift::DAYS;

        return view('company.schedule.index', compact('recurring', 'upcoming', 'days'));
    }
}
