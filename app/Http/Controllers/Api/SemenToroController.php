<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SemenToro;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class SemenToroController extends Controller
{
    public function index(Request $request)
    {
        $query = SemenToro::with('toro');

        if ($request->has('toro_id')) {
            $query->forToro($request->toro_id);
        }
        if ($request->has('activo')) {
            if ($request->activo == '1') {
                $query->activo();
            }
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Semen de toro',
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
            'id_Toro'     => 'required|exists:animal,id_Animal',
            'semen_estado'=> 'nullable|boolean',
            'semen_fecha' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $semen = SemenToro::create($request->only(['id_Toro', 'semen_estado', 'semen_fecha']));

        return response()->json(['success' => true, 'message' => 'Semen registrado', 'data' => $semen->load('toro')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $semen = SemenToro::with('toro', 'servicios')->find($id);
        if (!$semen) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $semen]);
    }

    public function update(Request $request, $id)
    {
        $semen = SemenToro::find($id);
        if (!$semen) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'semen_estado' => 'nullable|boolean',
            'semen_fecha'  => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $semen->update($request->only(['semen_estado', 'semen_fecha']));

        return response()->json(['success' => true, 'message' => 'Semen actualizado', 'data' => $semen]);
    }

    public function destroy($id)
    {
        $semen = SemenToro::find($id);
        if (!$semen) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $semen->delete();
        return response()->json(['success' => true, 'message' => 'Registro eliminado']);
    }
}
