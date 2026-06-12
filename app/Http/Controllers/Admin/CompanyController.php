<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::withCount('drivers');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('city', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $companies = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'     => Company::count(),
            'active'    => Company::where('status', 'active')->count(),
            'pending'   => Company::where('status', 'pending')->count(),
            'suspended' => Company::where('status', 'suspended')->count(),
        ];

        return view('admin.companies.index', compact('companies', 'stats'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:companies,email',
            'password'        => 'required|string|min:8|confirmed',
            'phone'           => 'nullable|string|max:20',
            'type'            => 'required|in:location,covoiturage,both',
            'status'          => 'required|in:active,suspended,pending',
            'city'            => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:255',
            'contact_name'    => 'nullable|string|max:150',
            'description'     => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $data['password'] = Hash::make($data['password']);

        Company::create($data);

        return redirect()->route('admin.companies.index')
                         ->with('success', 'Société créée avec succès.');
    }

    public function show(Company $company)
    {
        $company->load('drivers');
        $availableDrivers = Driver::whereNull('company_id')
                                  ->where('status', 'approved')
                                  ->get();
        return view('admin.companies.show', compact('company', 'availableDrivers'));
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:companies,email,' . $company->id,
            'phone'           => 'nullable|string|max:20',
            'type'            => 'required|in:location,covoiturage,both',
            'status'          => 'required|in:active,suspended,pending',
            'city'            => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:255',
            'contact_name'    => 'nullable|string|max:150',
            'description'     => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $company->update($data);

        return redirect()->route('admin.companies.show', $company)
                         ->with('success', 'Société mise à jour.');
    }

    public function destroy(Company $company)
    {
        // Détacher les chauffeurs avant suppression
        Driver::where('company_id', $company->id)->update(['company_id' => null]);
        $company->delete();

        return redirect()->route('admin.companies.index')
                         ->with('success', 'Société supprimée.');
    }

    public function suspend(Company $company)
    {
        $company->update(['status' => 'suspended']);
        return back()->with('success', 'Société suspendue.');
    }

    public function activate(Company $company)
    {
        $company->update(['status' => 'active']);
        return back()->with('success', 'Société activée.');
    }

    public function assignDriver(Request $request, Company $company)
    {
        $request->validate(['driver_id' => 'required|exists:drivers,id']);
        Driver::where('id', $request->driver_id)->update(['company_id' => $company->id]);
        return back()->with('success', 'Chauffeur assigné à la société.');
    }

    public function removeDriver(Company $company, Driver $driver)
    {
        if ($driver->company_id === $company->id) {
            $driver->update(['company_id' => null]);
        }
        return back()->with('success', 'Chauffeur retiré de la société.');
    }
}
