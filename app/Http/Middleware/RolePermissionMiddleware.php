<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RolePermissionMiddleware
{

    public function handle(Request $request, Closure $next, $roles = null, $permission = null)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié'
            ], 401);
        }


        // Super Admin = accès total
        if ($user->role && $user->role->name === "Super Admin") {
            return $next($request);
        }



        // Vérification roles multiples
        if ($roles) {

            $rolesArray = explode('|', $roles);

            if (!$user->role || !in_array($user->role->name, $rolesArray)) {

                return response()->json([
                    'message' => 'Rôle non autorisé'
                ], 403);

            }
        }



        // Vérification permission
        if ($permission) {

            if (!$user->hasPermission($permission)) {

                return response()->json([
                    'message' => 'Permission refusée'
                ], 403);

            }
        }


        return $next($request);

    }

}