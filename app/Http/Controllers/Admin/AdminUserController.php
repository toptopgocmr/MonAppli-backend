<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\AdminUser;
use App\Models\AdminLog;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{


    // LISTE ADMINS
    public function index(Request $request)
    {

        if(!$request->user()->hasPermission('view_admins')){
            return response()->json(['message'=>'Permission refusée'],403);
        }

        return response()->json(
            AdminUser::with('role')->paginate(20)
        );

    }



    // CREATION ADMIN
    public function store(Request $request)
    {

        if(!$request->user()->hasPermission('create_admin')){
            return response()->json(['message'=>'Permission refusée'],403);
        }


        $request->validate([

            'first_name'=>'required|string',
            'last_name'=>'required|string',

            'email'=>'required|email|unique:admin_users,email',

            'role_id'=>'required|exists:roles,id',

            'password'=>'required|min:6'

        ]);


        $admin = AdminUser::create([

            ...$request->only([
                'first_name',
                'last_name',
                'email',
                'phone',
                'role_id'
            ]),

            'password'=>Hash::make($request->password)

        ]);



        // LOG

        AdminLog::create([

            'admin_id'=>$request->user()->id,

            'action'=>'create_admin',

            'model'=>'AdminUser',

            'model_id'=>$admin->id,

            'ip_address'=>$request->ip()

        ]);


        return response()->json([

            'message'=>'Admin créé',

            'admin'=>$admin->load('role')

        ],201);

    }



    // UPDATE ADMIN
    public function update(Request $request,$id)
    {

        if(!$request->user()->hasPermission('edit_admin')){
            return response()->json(['message'=>'Permission refusée'],403);
        }


        $admin = AdminUser::findOrFail($id);


        $request->validate([

            'first_name'=>'string',

            'last_name'=>'string',

            'phone'=>'string',

            'role_id'=>'exists:roles,id',

            'status'=>'in:active,inactive'

        ]);


        $admin->update(

            $request->only([
                'first_name',
                'last_name',
                'phone',
                'role_id',
                'status'
            ])

        );


        // LOG

        AdminLog::create([

            'admin_id'=>$request->user()->id,

            'action'=>'update_admin',

            'model'=>'AdminUser',

            'model_id'=>$admin->id,

            'ip_address'=>$request->ip()

        ]);


        return response()->json([

            'message'=>'Admin modifié',

            'admin'=>$admin->load('role')

        ]);

    }




    // DELETE ADMIN
    public function destroy(Request $request,$id)
    {


        if(!$request->user()->hasPermission('delete_admin')){
            return response()->json(['message'=>'Permission refusée'],403);
        }



        $admin = AdminUser::findOrFail($id);



        // Protection super admin

        if($admin->role->name == "Super Admin"){

            return response()->json([
                'message'=>'Impossible de supprimer Super Admin'
            ],403);

        }



        $admin->delete();



        // LOG

        AdminLog::create([

            'admin_id'=>$request->user()->id,

            'action'=>'delete_admin',

            'model'=>'AdminUser',

            'model_id'=>$id,

            'ip_address'=>$request->ip()

        ]);



        return response()->json([

            'message'=>'Admin supprimé'

        ]);

    }



}