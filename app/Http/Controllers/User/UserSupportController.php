<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSupportController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => [],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé au support.',
        ]);
    }
}