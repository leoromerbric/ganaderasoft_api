<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FincaController;
use App\Http\Controllers\Api\PropietarioController;
use App\Http\Controllers\Api\RebanoController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\InventarioBufaloController;
use App\Http\Controllers\Api\TipoAnimalController;
use App\Http\Controllers\Api\EstadoSaludController;
use App\Http\Controllers\Api\EstadoAnimalController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\ComposicionRazaController;
use App\Http\Controllers\Api\EtapaController;
use App\Http\Controllers\Api\PersonalFincaController;
use App\Http\Controllers\Api\PesoCorporalController;
use App\Http\Controllers\Api\LactanciaController;
use App\Http\Controllers\Api\LecheController;
use App\Http\Controllers\Api\MedidasCorporalesController;
use App\Http\Controllers\Api\CambiosAnimalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Core entity CRUD routes
    Route::apiResource('fincas', FincaController::class);
    Route::apiResource('propietarios', PropietarioController::class);
    Route::apiResource('rebanos', RebanoController::class);
    Route::apiResource('animales', AnimalController::class);
    Route::apiResource('inventarios-bufalo', InventarioBufaloController::class);
    Route::apiResource('tipos-animal', TipoAnimalController::class);
    Route::apiResource('estados-salud', EstadoSaludController::class);
    Route::apiResource('estados-animal', EstadoAnimalController::class);
    Route::apiResource('composicion-raza', ComposicionRazaController::class);
    Route::apiResource('etapas', EtapaController::class);
    
    // New entity CRUD routes
    Route::apiResource('personal-finca', PersonalFincaController::class);
    Route::apiResource('peso-corporal', PesoCorporalController::class);
    Route::apiResource('lactancia', LactanciaController::class);
    Route::apiResource('leche', LecheController::class);
    Route::apiResource('medidas-corporales', MedidasCorporalesController::class);
    Route::apiResource('cambios-animal', CambiosAnimalController::class);
    
    // Configuration routes (JSON-based)
    Route::prefix('configuracion')->group(function () {
        Route::get('tipo-explotacion', [ConfiguracionController::class, 'tipoExplotacion']);
        Route::get('metodo-riego', [ConfiguracionController::class, 'metodoRiego']);
        Route::get('ph-suelo', [ConfiguracionController::class, 'phSuelo']);
        Route::get('textura-suelo', [ConfiguracionController::class, 'texturaSuelo']);
        Route::get('fuente-agua', [ConfiguracionController::class, 'fuenteAgua']);
        Route::get('sexo', [ConfiguracionController::class, 'sexo']);
        Route::get('tipo-relieve', [ConfiguracionController::class, 'tipoRelieve']);
    });
    
    // Animal relationship management routes
    Route::prefix('animales/{animal}')->group(function () {
        Route::post('estado-animal', [AnimalController::class, 'createEstadoAnimal']);
        Route::put('estado-animal/{estado}', [AnimalController::class, 'updateEstadoAnimal']);
        Route::post('etapa-animal', [AnimalController::class, 'createEtapaAnimal']);
        Route::put('etapa-animal/{etapa}', [AnimalController::class, 'updateEtapaAnimal']);
    });
});
