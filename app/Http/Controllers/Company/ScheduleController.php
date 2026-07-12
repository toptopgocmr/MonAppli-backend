<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverShift;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private function company()
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        return \App\Support\CompanyContext::company();
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

        // ✅ Permet de planifier directement depuis cette page (avant : il
        // fallait obligatoirement passer par la fiche d'un véhicule).
        $vehicles = Vehicle::where('company_id', $company->id)->orderBy('plate')->get();
        $drivers  = Driver::where('company_id', $company->id)->orderBy('first_name')->get();

        return view('company.schedule.index', compact('recurring', 'upcoming', 'days', 'vehicles', 'drivers'));
    }

    // Planifier un chauffeur sur un véhicule directement depuis le planning
    // (même logique que VehicleController::storeShift, mais le véhicule est
    // choisi dans le formulaire plutôt que dans l'URL).
    public function store(Request $request)
    {
        $company = $this->company();

        $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'driver_id'      => 'required|exists:drivers,id',
            'schedule_mode'  => 'required|in:recurring,specific',
            'day_of_week'    => 'required_if:schedule_mode,recurring|nullable|integer|min:0|max:6',
            'specific_date'  => 'required_if:schedule_mode,specific|nullable|date',
            'start_time'     => 'nullable|date_format:H:i',
            'end_time'       => 'nullable|date_format:H:i',
            'is_primary'     => 'nullable|boolean',
        ]);

        $vehicle = Vehicle::where('company_id', $company->id)->findOrFail($request->vehicle_id);
        $driver  = Driver::where('company_id', $company->id)->findOrFail($request->driver_id);

        VehicleDriverShift::create([
            'vehicle_id'    => $vehicle->id,
            'driver_id'     => $driver->id,
            'day_of_week'   => $request->schedule_mode === 'recurring' ? $request->day_of_week : null,
            'specific_date' => $request->schedule_mode === 'specific' ? $request->specific_date : null,
            'start_time'    => $request->start_time,
            'end_time'      => $request->end_time,
            'is_primary'    => (bool) $request->is_primary,
            'status'        => 'active',
        ]);

        // Le chauffeur assigné récupère les infos du véhicule (compat écrans existants : trajets, admin, KYC)
        $driver->update([
            'vehicle_plate'    => $vehicle->plate,
            'vehicle_brand'    => $vehicle->brand,
            'vehicle_model'    => $vehicle->model,
            'vehicle_color'    => $vehicle->color,
            'vehicle_type'     => $vehicle->type ?: $driver->vehicle_type,
            'vehicle_city'     => $vehicle->city ?? $driver->vehicle_city,
            'vehicle_country'  => $vehicle->country ?? $driver->vehicle_country,
        ]);

        return back()->with('success', 'Créneau planifié avec succès.');
    }
}
