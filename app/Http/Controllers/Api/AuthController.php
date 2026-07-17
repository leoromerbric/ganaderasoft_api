<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Resources\User\UserResource;
use App\Http\Middleware\Legacy\User\NormalizeLogin;
use App\Http\Middleware\Legacy\User\NormalizeRegister;
use App\Http\Middleware\Legacy\User\NormalizeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta los middlewares de compatibilidad para el front-end legacy.
     */
    public function __construct()
    {
        $this->middleware(NormalizeLogin::class)->only('login');
        $this->middleware(NormalizeRegister::class)->only('register');
        $this->middleware(NormalizeProfile::class)->only('profile');
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $user->load('roles');
        $token = $user->createToken('GanaderaSoft API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => $this->formatResource(UserResource::class, $user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'personas.propietario.persona']);
        
        return response()->json([
            'success' => true,
            'message' => 'Perfil de usuario',
            'data' => [
                'user' => $this->formatResource(UserResource::class, $user)
            ]
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_code' => 'required|string|exists:roles,code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active'
        ]);

        // Asignar rol
        $role = Role::where('code', $request->role_code)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        $user->load('roles');
        $token = $user->createToken('GanaderaSoft API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => $this->formatResource(UserResource::class, $user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], Response::HTTP_CREATED);
    }
}