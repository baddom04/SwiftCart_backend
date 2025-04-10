<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:50|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return response()->json(['message' => "User updated successfully"], 200);
    }

    public function update_password(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'The current password is incorrect.'
            ], 403);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['Message' => 'Password updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:20',
            'email'    => 'required|email|unique:users|max:50',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $user = User::factory()->create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'admin'    => false,
        ]);

        $token = $user->createToken($user->email, $user->admin ? ['swift_cart:admin'] : []);

        return response()->json([
            'token' => $token->plainTextToken,
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:50',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        if (!Auth::attempt($validator->validated())) {
            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $validated['email'])->first();

        $token = $user->createToken($user->email, $user->admin ? ['swift_cart:admin'] : []);

        return response()->json([
            'token' => $token->plainTextToken,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([], 200);
    }

    public function user(Request $request)
    {
        return $request->user();
    }
}
