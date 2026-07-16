<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sanidad\TratamientoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TratamientoController extends Controller
{
    protected $tratamientoService;

    public function __construct(TratamientoService $tratamientoService)
    {
        $this->tratamientoService = $tratamientoService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['diagnostico_id', 'fecha_inicio', 'fecha_fin']);
        
        $records = $this->tratamientoService->getPaginatedTratamientos($filters, $request->user());

        return response()->json([
            'success'    => true,
            'message'    => 'Tratamientos obtenidos exitosamente',
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
            'plan'           => 'nullable|string|max:255',
            'fecha_ini'      => 'required|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_ini',
            'diagnostico_id' => 'nullable|exists:diagnosticos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tratamiento = $this->tratamientoService->createTratamiento($request->only([
            'plan', 'fecha_ini', 'fecha_fin', 'diagnostico_id'
        ]));

        return response()->json([
            'success' => true, 
            'message' => 'Tratamiento registrado', 
            'data'    => $tratamiento
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $tratamiento = $this->tratamientoService->getTratamientoById($id);

        if (!$tratamiento) {
            return response()->json(['success' => false, 'message' => 'Tratamiento no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['success' => true, 'data' => $tratamiento]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'plan'           => 'nullable|string|max:255',
            'fecha_ini'      => 'sometimes|date',
            'fecha_fin'      => 'nullable|date|after_or_equal:fecha_ini',
            'diagnostico_id' => 'nullable|exists:diagnosticos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tratamiento = $this->tratamientoService->updateTratamiento($id, $request->only([
            'plan', 'fecha_ini', 'fecha_fin', 'diagnostico_id'
        ]));

        if (!$tratamiento) {
            return response()->json(['success' => false, 'message' => 'Tratamiento no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['success' => true, 'message' => 'Tratamiento actualizado', 'data' => $tratamiento]);
    }

    public function destroy($id)
    {
        $deleted = $this->tratamientoService->deleteTratamiento($id);

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Tratamiento no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['success' => true, 'message' => 'Tratamiento eliminado']);
    }
}