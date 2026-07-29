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
use App\Services\User\AuthService;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

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
     * Iniciar sesión del usuario y crear token
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
     * Obtener perfil del usuario autenticado
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
     * Cerrar sesión del usuario (revocar token)
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
     * Registrar un nuevo usuario
     */
    public function register(Request $request, AuthService $authService)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => 'required|string|max:20',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'required|string|email|max:255|unique:users,email|unique:personas,correo',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|same:password',
            'role_code' => 'required|string|exists:roles,code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $authService->registerUser($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user' => $this->formatResource(UserResource::class, $user)
                ]
            ], Response::HTTP_CREATED);

        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}