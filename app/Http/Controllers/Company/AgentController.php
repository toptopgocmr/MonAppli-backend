<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    public function index()
    {
        $company = $this->company();
        $agents = CompanyAgent::where('company_id', $company->id)
                              ->orderBy('created_at', 'desc')
                              ->get();

        return view('company.agents.index', compact('agents'));
    }

    public function create()
    {
        return view('company.agents.create');
    }

    public function store(Request $request)
    {
        $company = $this->company();

        $request->validate([
            'name'        => 'required|string|max:150',
            'email'       => 'required|email|unique:company_agents,email',
            'phone'       => 'nullable|string|max:20',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => 'required|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(CompanyAgent::PERMISSIONS)),
        ]);

        CompanyAgent::create([
            'company_id'  => $company->id,
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            // Le Directeur Général a toujours un accès complet (géré dans le modèle),
            // mais on stocke aussi la liste complète pour rester cohérent à l'affichage.
            'permissions' => $request->role === 'directeur_general'
                ? array_keys(CompanyAgent::PERMISSIONS)
                : ($request->permissions ?? []),
            'status'      => 'active',
        ]);

        return redirect()->route('company.agents.index')
                         ->with('success', 'Agent ajouté avec succès.');
    }

    public function edit($id)
    {
        $agent = CompanyAgent::where('company_id', $this->company()->id)->findOrFail($id);
        return view('company.agents.edit', compact('agent'));
    }

    public function update(Request $request, $id)
    {
        $company = $this->company();
        $agent = CompanyAgent::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:150',
            'email'       => 'required|email|unique:company_agents,email,' . $agent->id,
            'phone'       => 'nullable|string|max:20',
            'password'    => 'nullable|string|min:8|confirmed',
            'role'        => 'required|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(CompanyAgent::PERMISSIONS)),
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role'  => $request->role,
            'permissions' => $request->role === 'directeur_general'
                ? array_keys(CompanyAgent::PERMISSIONS)
                : ($request->permissions ?? []),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $agent->update($data);

        return redirect()->route('company.agents.index')
                         ->with('success', 'Agent mis à jour.');
    }

    public function suspend($id)
    {
        $agent = CompanyAgent::where('company_id', $this->company()->id)->findOrFail($id);
        $agent->update(['status' => 'suspended']);
        return back()->with('success', 'Accès agent suspendu.');
    }

    public function activate($id)
    {
        $agent = CompanyAgent::where('company_id', $this->company()->id)->findOrFail($id);
        $agent->update(['status' => 'active']);
        return back()->with('success', 'Accès agent réactivé.');
    }

    public function destroy($id)
    {
        $agent = CompanyAgent::where('company_id', $this->company()->id)->findOrFail($id);
        $agent->delete();
        return back()->with('success', 'Agent supprimé.');
    }
}
