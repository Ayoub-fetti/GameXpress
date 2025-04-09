<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;

class UserAuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (User::count() === 1) {
            $role = Role::findByName('super_admin');
        } else {
            $role = Role::findByName('user');
        }

        $user->assignRole($role);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Les identifiants fournis sont incorrects.',
            'errors' => ['email' => ['Les identifiants fournis sont incorrects.']]
        ], 401);
    }
    
    // Supprimer les anciens tokens (optionnel)
    // $user->tokens()->delete();
    
    $token = $user->createToken('auth-token')->plainTextToken;
    
    $sessionId = $request->header('X-Session-Id');
    $mergeResult = null;
    

    if ($sessionId) {

        $cartController = new \App\Http\Controllers\Api\V1\CartController();
        $mergeResult = $cartController->mergeGuestCart($sessionId, $user->id);
    }
    
    return response()->json([
        'message' => 'Connexion réussie',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ],
        'token' => $token,
        'merge_result' => $sessionId ? 'Fusion du panier tentée' : 'Aucun panier à fusionner'
    ], 200);
}

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully'], 200);
        }

        return response()->json(['message' => 'No authenticated user'], 401);
    }

    public function assignRolesAndPermissions(Request $request, $userId)
    {
        if (!$request->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
            'permessions' => 'array',
            'permissions.*' => 'boolean',
        ]);
        $user = User::findOrFail($userId);


        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        
        if ($request->has('permissions')) {
            $permissions = array_keys(array_filter($request->permissions));
            $user->syncPermissions($permissions);
        }

        return response()->json(['message' => 'Roles and permissions assigned successfully'], 200);
    }



}