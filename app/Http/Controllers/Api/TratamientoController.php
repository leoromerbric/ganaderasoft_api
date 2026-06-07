<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tratamiento;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TratamientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tratamiento::with('diagnostico');

        if ($request->has('diagnostico_id')) {
            $query->forDiagnostico($request->diagnostico_id);
        }
        if ($request->has('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Tratamientos',
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
            'tratamiento_plan'          => 'nullable|string|max:255',
            'tratamiento_fecha_ini'     => 'required|date',
            'tratamiento_fecha_fin'     => 'required|date|after_or_equal:tratamiento_fecha_ini',
            'tratamiento_diagnostico_id'=> 'nullable|exists:diagnostico,diagnostico_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tratamiento = Tratamiento::create($request->only([
            'tratamiento_plan', 'tratamiento_fecha_ini',
            'tratamiento_fecha_fin', 'tratamiento_diagnostico_id',
        ]));

        return response()->json(['success' => true, 'message' => 'Tratamiento registrado', 'data' => $tratamiento->load('diagnostico')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $tratamiento = Tratamiento::with('diagnostico')->find($id);
        if (!$tratamiento) {
            return response()->json(['success' => false, 'message' => 'Tratamiento no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $tratamiento]);
    }

    public function update(Request $request, $id)
    {
        $tratamiento = Tratamiento::find($id);
        if (!$tratamiento) {
            return response()->json(['success' => false, 'message' => 'Tratamiento no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'tratamiento_plan'      => 'nullable|string|max:255',
            'tratamiento_fecha_ini' => 'sometimes|date',
            'tratamiento_fecha_fin' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tratamiento->update($request->only(['tratamiento_plan', 'tratamiento_fecha_ini', 'tratamiento_fecha_fin']));

        return response()->json(['success' => true, 'message' => 'Tratamiento actualizado', 'data' => $tratamiento]);
    }

    public function destroy($id)
    {
        $tratamiento = Tratamiento::find($id);
        if (!$tratamiento) {
            return response()->json(['success' => false, 'message' => 'Tratamiento no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $tratamiento->delete();
        return response()->json(['success' => true, 'message' => 'Tratamiento eliminado']);
    }
}
