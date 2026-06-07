<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoricoAplicacion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class HistoricoAplicacionController extends Controller
{
    public function index(Request $request)
    {
        $query = HistoricoAplicacion::with('vacuna', 'casaComercial', 'dosis');

        if ($request->has('vacuna_id')) {
            $query->byVacuna($request->vacuna_id);
        }
        if ($request->has('dosis_id')) {
            $query->where('ha_dosis_id', $request->dosis_id);
        }
        if ($request->has('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Histórico de aplicaciones',
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
            'ha_vacuna_id'   => 'required|exists:vacuna,vacuna_id',
            'ha_casa_id'     => 'required|exists:casa_comercial,casa_id',
            'ha_dosis_id'    => 'required|exists:dosis,dosis_id',
            'fecha_inyeccion'=> 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ha = HistoricoAplicacion::create($request->only(['ha_vacuna_id', 'ha_casa_id', 'ha_dosis_id', 'fecha_inyeccion']));

        return response()->json(['success' => true, 'message' => 'Aplicación registrada', 'data' => $ha->load('vacuna', 'casaComercial')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $ha = HistoricoAplicacion::with('vacuna', 'casaComercial', 'dosis')->find($id);
        if (!$ha) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $ha]);
    }

    public function update(Request $request, $id)
    {
        $ha = HistoricoAplicacion::find($id);
        if (!$ha) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'fecha_inyeccion' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ha->update($request->only(['fecha_inyeccion']));

        return response()->json(['success' => true, 'message' => 'Aplicación actualizada', 'data' => $ha]);
    }

    public function destroy($id)
    {
        $ha = HistoricoAplicacion::find($id);
        if (!$ha) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $ha->delete();
        return response()->json(['success' => true, 'message' => 'Registro eliminado']);
    }
}
