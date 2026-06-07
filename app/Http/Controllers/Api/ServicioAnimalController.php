<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServicioAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ServicioAnimalController extends Controller
{
    public function index(Request $request)
    {
        $query = ServicioAnimal::with('animal', 'semen', 'tecnico', 'registroCelo');

        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }
        if ($request->has('tipo')) {
            $query->byTipo($request->tipo);
        }
        if ($request->has('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Registros de servicio',
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
            'servicio_id_Animal'  => 'required|exists:animal,id_Animal',
            'servicio_semen_id'   => 'nullable|exists:semen_toro,semen_id',
            'servicio_id_Tecnico' => 'nullable|exists:personal_finca,id_Personal',
            'servicio_tipo'       => 'nullable|string|max:11',
            'servicio_fecha'      => 'nullable|date',
            'servicio_observacion'=> 'nullable|string|max:100',
            'servicio_celo_id'    => 'nullable|exists:registro_celo,celo_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $servicio = ServicioAnimal::create($request->only([
            'servicio_id_Animal', 'servicio_semen_id', 'servicio_id_Tecnico',
            'servicio_tipo', 'servicio_fecha', 'servicio_observacion', 'servicio_celo_id',
        ]));

        return response()->json(['success' => true, 'message' => 'Servicio registrado', 'data' => $servicio->load('animal', 'semen')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $servicio = ServicioAnimal::with('animal', 'semen', 'tecnico', 'registroCelo')->find($id);
        if (!$servicio) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $servicio]);
    }

    public function update(Request $request, $id)
    {
        $servicio = ServicioAnimal::find($id);
        if (!$servicio) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'servicio_semen_id'   => 'nullable|exists:semen_toro,semen_id',
            'servicio_id_Tecnico' => 'nullable|exists:personal_finca,id_Personal',
            'servicio_tipo'       => 'nullable|string|max:11',
            'servicio_fecha'      => 'nullable|date',
            'servicio_observacion'=> 'nullable|string|max:100',
            'servicio_celo_id'    => 'nullable|exists:registro_celo,celo_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $servicio->update($request->only([
            'servicio_semen_id', 'servicio_id_Tecnico', 'servicio_tipo',
            'servicio_fecha', 'servicio_observacion', 'servicio_celo_id',
        ]));

        return response()->json(['success' => true, 'message' => 'Servicio actualizado', 'data' => $servicio]);
    }

    public function destroy($id)
    {
        $servicio = ServicioAnimal::find($id);
        if (!$servicio) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $servicio->delete();
        return response()->json(['success' => true, 'message' => 'Servicio eliminado']);
    }
}
