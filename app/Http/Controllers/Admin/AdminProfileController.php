<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    /**
     * Liste de tous les administrateurs
     */
    public function index()
    {
        $admins = AdminUser::with('role')->orderBy('created_at', 'desc')->get();
        $roles  = Role::all();

        return view('admin.profiles.index', compact('admins', 'roles'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.profiles.create', compact('roles'));
    }

    /**
     * Enregistrer un nouvel admin
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:admin_users,email',
            'phone'      => 'nullable|string|max:20',
            'role_id'    => 'required|exists:roles,id',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        AdminUser::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'role_id'    => $request->role_id,
            'password'   => Hash::make($request->password),
            'status'     => 'active',
        ]);

        return redirect()->route('admin.profiles.index')
            ->with('success', 'Administrateur créé avec succès.');
    }


    /**
     * Détail d'un admin
     */
    public function show($id)
    {
        $admin = AdminUser::with('role')->findOrFail($id);
        return view('admin.profiles.show', compact('admin'));
    }

    /**
     * Formulaire de modification
     */
    public function edit($id)
    {
        $admin = AdminUser::findOrFail($id);
        $roles = Role::all();

        return view('admin.profiles.edit', compact('admin', 'roles'));
    }

    /**
     * Mettre à jour un admin
     */
    public function update(Request $request, $id)
    {
        $admin = AdminUser::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:admin_users,email,' . $id,
            'phone'      => 'nullable|string|max:20',
            'role_id'    => 'required|exists:roles,id',
            'password'   => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'role_id'    => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return redirect()->route('admin.profiles.index')
            ->with('success', 'Administrateur modifié avec succès.');
    }

    /**
     * Bloquer un admin (status = inactive)
     */
    public function block($id)
    {
        $admin = AdminUser::findOrFail($id);

        // Empêcher de se bloquer soi-même
        if ($admin->id === session('admin_id')) {
            return back()->with('error', 'Vous ne pouvez pas vous bloquer vous-même.');
        }

        $admin->update(['status' => 'inactive']);

        // Révoquer tous ses tokens
        $admin->tokens()->delete();

        return back()->with('success', $admin->first_name . ' a été bloqué.');
    }

    /**
     * Activer un admin (status = active)
     */
    public function activate($id)
    {
        $admin = AdminUser::findOrFail($id);
        $admin->update(['status' => 'active']);

        return back()->with('success', $admin->first_name . ' a été activé.');
    }

    /**
     * Supprimer un admin
     */
    public function destroy($id)
    {
        $admin = AdminUser::findOrFail($id);

        if ($admin->id === session('admin_id')) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $admin->tokens()->delete();
        $admin->delete();

        return back()->with('success', 'Administrateur supprimé.');
    }
}