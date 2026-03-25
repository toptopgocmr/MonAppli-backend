<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $users    = $query->paginate(15);
        $countries = User::select('country')->distinct()->pluck('country');

        return view('admin.users.index', compact('users', 'countries'));
    }

    /**
     * Détail d'un utilisateur
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Bloquer un utilisateur
     */
    public function block($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'inactive']);

        return back()->with('success', $user->first_name . ' a été bloqué.');
    }

    /**
     * Activer un utilisateur
     */
    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return back()->with('success', $user->first_name . ' a été activé.');
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }
}