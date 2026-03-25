<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

    public function update(Request $request)
    {
        $request->validate([
            'first_name'    => 'sometimes|string|max:100',
            'last_name'     => 'sometimes|string|max:100',
            'email'         => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'city'          => 'sometimes|string|max:100',
            'country'       => 'sometimes|string|max:100',
        ]);

        $request->user()->update($request->only(['first_name', 'last_name', 'email', 'city', 'country']));

        return new UserResource($request->user()->fresh());
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:3072',
        ]);

        $path = $this->fileUploadService->uploadProfilePhoto($request->file('photo'), 'users');
        $request->user()->update(['profile_photo' => $path]);

        return response()->json(['message' => 'Photo mise à jour.', 'path' => $path]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}
