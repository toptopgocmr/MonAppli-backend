<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    public function index(Request $request)
    {
        $company = $this->company();
        $query   = Driver::where('company_id', $company->id)->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name',  'like', '%' . $request->search . '%')
                  ->orWhere('phone',      'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $drivers = $query->paginate(15);
        return view('company.drivers.index', compact('drivers'));
    }

    public function show($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        return view('company.drivers.show', compact('driver'));
    }

    // Affecter un chauffeur indépendant à la société (si admin l'a autorisé)
    public function assign(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        if ($driver->company_id) {
            return back()->with('error', 'Ce chauffeur est déjà affecté à une société.');
        }

        $driver->update(['company_id' => $this->company()->id]);
        return back()->with('success', 'Chauffeur affecté à votre société.');
    }

    // Retirer un chauffeur de la société
    public function remove($id)
    {
        $driver = Driver::where('company_id', $this->company()->id)->findOrFail($id);
        $driver->update(['company_id' => null]);
        return back()->with('success', 'Chauffeur retiré de votre société.');
    }
}
