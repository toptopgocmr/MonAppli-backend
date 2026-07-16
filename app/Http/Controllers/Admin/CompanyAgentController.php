<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyAgent;
use Illuminate\Http\Request;

/**
 * Vue plateforme (super admin) sur les comptes agents des sociétés
 * (comptable, RH, directeur général, flotte, marketing, commercial...).
 *
 * Ces comptes sont créés/gérés par chaque société elle-même
 * (App\Http\Controllers\Company\AgentController), mais jusqu'ici le super
 * admin n'avait aucune page pour les voir, toutes sociétés confondues.
 */
class CompanyAgentController extends Controller
{
    public function index(Request $request)
    {
        $query = CompanyAgent::with('company');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total'     => CompanyAgent::count(),
            'active'    => CompanyAgent::where('status', 'active')->count(),
            'suspended' => CompanyAgent::where('status', 'suspended')->count(),
        ];

        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('admin.company-agents.index', compact('agents', 'stats', 'companies'));
    }

    public function show($id)
    {
        $agent = CompanyAgent::with('company')->findOrFail($id);

        return view('admin.company-agents.show', compact('agent'));
    }

    public function suspend($id)
    {
        $agent = CompanyAgent::findOrFail($id);
        $agent->update(['status' => 'suspended']);

        return back()->with('success', 'Accès agent suspendu.');
    }

    public function activate($id)
    {
        $agent = CompanyAgent::findOrFail($id);
        $agent->update(['status' => 'active']);

        return back()->with('success', 'Accès agent réactivé.');
    }
}
