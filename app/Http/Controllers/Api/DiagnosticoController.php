<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sanidad\DiagnosticoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class DiagnosticoController extends Controller
{
    protected $diagnosticoService;

    public function __construct(DiagnosticoService $diagnosticoService)
    {
        $this->diagnosticoService = $diagnosticoService;
    }

    public function index(Request $request)
    {
        // Extract only the necessary filter parameters
        $filters = $request->only(['animal_id', 'tipo', 'fecha_inicio', 'fecha_fin']);
        
        // Delegate to the service
        $records = $this->diagnosticoService->getPaginatedDiagnosticos($filters, $request->user());

        return response()->json([
            'success'    => true,
            'message'    => 'Diagnósticos obtenidos exitosamente',
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
            'descripcion'     => 'nullable|string',
            'tipo'            => 'nullable|string|max:30',
            'fecha'           => 'nullable|date',
            'animal_etapa_id' => 'required|exists:animal_etapa,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Delegate creation to the service
        $diagnostico = $this->diagnosticoService->createDiagnostico($request->only([
            'descripcion', 'tipo', 'fecha', 'animal_etapa_id'
        ]));

        return response()->json([
            'success' => true, 
            'message' => 'Diagnóstico registrado', 
            'data'    => $diagnostico
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $diagnostico = $this->diagnosticoService->getDiagnosticoById($id);
        
        if (!$diagnostico) {
            return response()->json(['success' => false, 'message' => 'Diagnóstico no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['success' => true, 'data' => $diagnostico]);
    }

    public function update(Request $request, $id)
    {
        // It's usually better to validate before making database queries
        $validator = Validator::make($request->all(), [
            'descripcion'     => 'nullable|string',
            'tipo'            => 'nullable|string|max:30',
            'fecha'           => 'nullable|date',
            'animal_etapa_id' => 'nullable|exists:animal_etapa,id', 
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $diagnostico = $this->diagnosticoService->updateDiagnostico($id, $request->only([
            'descripcion', 'tipo', 'fecha', 'animal_etapa_id'
        ]));

        if (!$diagnostico) {
            return response()->json(['success' => false, 'message' => 'Diagnóstico no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['success' => true, 'message' => 'Diagnóstico actualizado', 'data' => $diagnostico]);
    }

    public function destroy($id)
    {
        $deleted = $this->diagnosticoService->deleteDiagnostico($id);
        
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Diagnóstico no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['success' => true, 'message' => 'Diagnóstico eliminado']);
    }
}