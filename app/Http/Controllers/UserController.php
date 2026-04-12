<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function profile(Request $request)
{
    $user = $request->user();

    return response()->json([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]
    ]);
}
public function profile_picture(Request $request)
{
    $request->validate([
        'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ]);

    $user = $request->user();

    // Delete old image if exists
    if ($user->profile_picture) {
        Storage::disk('public')->delete($user->profile_picture);
    }

    // Store new image
    $path = $request->file('image')->store('profile_pictures', 'public');

    // Save to user
    $user->update([
        'profile_picture' => $path,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'image_url' => asset('storage/' . $path),
    ]);
}
}
