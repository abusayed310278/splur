<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    // List all users with role info
    public function index()
    {
        $perPage = 10;

        $users = User::select('id', 'first_name', 'last_name', 'email', 'phone', 'role')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Transform each user item
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully.',
            'data' => $users->items(),  // transformed items
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    // Show single user
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ]
        ]);
    }

    // Update user role (admin only)
    public function update(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,editor,author,user',
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully.',
            'data' => [
                'id' => $user->id,
                'role' => $user->role
            ]
        ]);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}
