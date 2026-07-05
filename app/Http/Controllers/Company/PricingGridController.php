<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\PricingGrid;
use App\Models\PricingGridRate;
use Illuminate\Http\Request;

class PricingGridController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    public function index(Request $request)
    {
        $company = $this->company();

        $grids = PricingGrid::where('company_id', $company->id)
                            ->withCount('rates')
                            ->orderBy('created_at', 'desc')
                            ->get();

        return view('company.pricing-grids.index', compact('grids'));
    }

    public function create()
    {
        return view('company.pricing-grids.create');
    }

    public function store(Request $request)
    {
        $company = $this->company();

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $grid = PricingGrid::create([
            'company_id'  => $company->id,
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return redirect()->route('company.pricing-grids.show', $grid->id)
                         ->with('success', 'Grille tarifaire créée. Ajoutez maintenant vos tarifs.');
    }

    public function show($id)
    {
        $company = $this->company();
        $grid = PricingGrid::where('company_id', $company->id)
                           ->with(['rates' => fn ($q) => $q->orderBy('label')])
                           ->findOrFail($id);

        return view('company.pricing-grids.show', compact('grid'));
    }

    public function edit($id)
    {
        $company = $this->company();
        $grid = PricingGrid::where('company_id', $company->id)->findOrFail($id);
        return view('company.pricing-grids.edit', compact('grid'));
    }

    public function update(Request $request, $id)
    {
        $company = $this->company();
        $grid = PricingGrid::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ]);

        $grid->update([
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', $grid->is_active),
        ]);

        return redirect()->route('company.pricing-grids.show', $grid->id)
                         ->with('success', 'Grille tarifaire mise à jour.');
    }

    public function destroy($id)
    {
        $company = $this->company();
        $grid = PricingGrid::where('company_id', $company->id)->findOrFail($id);
        $grid->delete(); // cascade sur pricing_grid_rates ; les itinéraires liés repassent en pricing_grid_id = null

        return redirect()->route('company.pricing-grids.index')->with('success', 'Grille tarifaire supprimée.');
    }

    // ── Lignes de tarif (remplissage manuel) ─────────────────────────

    public function storeRate(Request $request, $id)
    {
        $company = $this->company();
        $grid = PricingGrid::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'label'        => 'required|string|max:100',
            'vehicle_type' => 'nullable|string|max:50',
            'price'        => 'required|numeric|min:0',
        ]);

        PricingGridRate::create([
            'pricing_grid_id' => $grid->id,
            'label'           => $request->label,
            'vehicle_type'    => $request->vehicle_type ?: null,
            'price'           => $request->price,
        ]);

        return back()->with('success', 'Tarif ajouté.');
    }

    public function destroyRate($id, $rateId)
    {
        $company = $this->company();
        $grid = PricingGrid::where('company_id', $company->id)->findOrFail($id);
        PricingGridRate::where('pricing_grid_id', $grid->id)->findOrFail($rateId)->delete();

        return back()->with('success', 'Tarif retiré.');
    }
}
