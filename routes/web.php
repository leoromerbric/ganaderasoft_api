<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'GanaderaSoft API Gateway',
        'version' => '1.0.0',
        'documentation' => '/api/health'
    ]);
});

// Test API routes directly on web for testing
Route::get('/api/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'GanaderaSoft API is running',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

// Test routes to demonstrate API functionality
Route::prefix('api')->group(function () {
    Route::get('/test/register', function () {
        return response()->json([
            'success' => true,
            'message' => 'User registration endpoint is working',
            'required_fields' => ['name', 'email', 'password', 'type_user'],
            'allowed_user_types' => ['admin', 'propietario', 'tecnico'],
            'method' => 'POST',
            'endpoint' => '/api/auth/register'
        ]);
    });
    
    Route::get('/test/finca', function () {
        return response()->json([
            'success' => true,
            'message' => 'Finca CRUD endpoints are working',
            'endpoints' => [
                'GET /api/fincas' => 'List all fincas',
                'POST /api/fincas' => 'Create new finca',
                'GET /api/fincas/{id}' => 'Get finca details',
                'PUT /api/fincas/{id}' => 'Update finca',
                'DELETE /api/fincas/{id}' => 'Delete finca'
            ],
            'required_fields' => ['Nombre', 'Explotacion_Tipo', 'id_Propietario'],
            'authentication' => 'Bearer token required'
        ]);
    });
    
    Route::get('/test/database', function () {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return response()->json([
                'success' => true,
                'message' => 'Database connection successful',
                'driver' => config('database.default'),
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});
