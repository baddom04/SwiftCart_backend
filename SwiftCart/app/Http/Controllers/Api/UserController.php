<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:5',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'admin'    => 'required|boolean'
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
            'admin'    => $validated['admin'],
        ]);

        $token = $user->createToken($user->email, $user->admin ? ['swift_cart:admin'] : []);

        return response()->json([
            'token' => $token->plainTextToken,
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
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
        ]);
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
