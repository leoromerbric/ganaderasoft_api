<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sanidad\PalpacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class PalpacionController extends Controller
{
    protected $palpacionService;

    public function __construct(PalpacionService $palpacionService)
    {
        $this->palpacionService = $palpacionService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['animal_id', 'tipo', 'fecha_inicio', 'fecha_fin']);
        
        $records = $this->palpacionService->getPaginatedPalpaciones($filters, $request->user());

        return response()->json([
            'success'    => true,
            'message'    => 'Palpaciones obtenidas exitosamente',
            'data'       => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personal_finca_id' => 'nullable|exists:personal_fincas,id',
            'tipo'              => 'nullable|string|max:16',
            'fecha'             => 'nullable|date',
            'animal_etapa_id'   => 'required|exists:animal_etapa,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $palpacion = $this->palpacionService->createPalpacion($request->only([
            'personal_finca_id', 'tipo', 'fecha', 'animal_etapa_id'
        ]));

        return response()->json([
            'success' => true, 
            'message' => 'Palpación registrada', 
            'data'    => $palpacion
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $palpacion = $this->palpacionService->getPalpacionById($id);

        if (!$palpacion) {
            return response()->json(['success' => false, 'message' => 'Palpación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['success' => true, 'data' => $palpacion]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'personal_finca_id' => 'nullable|exists:personal_fincas,id',
            'tipo'              => 'nullable|string|max:16',
            'fecha'             => 'nullable|date',
            'animal_etapa_id'   => 'nullable|exists:animal_etapa,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $palpacion = $this->palpacionService->updatePalpacion($id, $request->only([
            'personal_finca_id', 'tipo', 'fecha', 'animal_etapa_id'
        ]));

        if (!$palpacion) {
            return response()->json(['success' => false, 'message' => 'Palpación no encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['success' => true, 'message' => 'Palpación actualizada', 'data' => $palpacion]);
    }

    public function destroy($id)
    {
        $deleted = $this->palpacionService->deletePalpacion($id);

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Palpación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['success' => true, 'message' => 'Palpación eliminada']);
    }
}