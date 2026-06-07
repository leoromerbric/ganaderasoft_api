<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventarioVacuno;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class InventarioVacunoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = InventarioVacuno::with('finca');

        if ($request->has('id_finca')) {
            $query->forFinca($request->id_finca);
        }
        if ($request->has('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        if (!$user->isAdmin() && $user->isPropietario()) {
            $query->whereHas('finca', fn($q) => $q->where('id_Propietario', $user->propietario->id));
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Inventarios vacunos',
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
            'id_Finca'         => 'required|exists:finca,id_Finca',
            'Num_Becerra'      => 'nullable|integer|min:0',
            'Num_Mauta'        => 'nullable|integer|min:0',
            'Num_Novilla'      => 'nullable|integer|min:0',
            'Num_Vaca'         => 'nullable|integer|min:0',
            'Num_Becerro'      => 'nullable|integer|min:0',
            'Num_Maute'        => 'nullable|integer|min:0',
            'Num_Torete'       => 'nullable|integer|min:0',
            'Num_Toro'         => 'nullable|integer|min:0',
            'Fecha_Inventario' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $inv = InventarioVacuno::create($request->only([
            'id_Finca', 'Num_Becerra', 'Num_Mauta', 'Num_Novilla', 'Num_Vaca',
            'Num_Becerro', 'Num_Maute', 'Num_Torete', 'Num_Toro', 'Fecha_Inventario',
        ]));

        return response()->json(['success' => true, 'message' => 'Inventario vacuno creado', 'data' => $inv], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $inv = InventarioVacuno::with('finca')->find($id);
        if (!$inv) {
            return response()->json(['success' => false, 'message' => 'Inventario no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $inv]);
    }

    public function update(Request $request, $id)
    {
        $inv = InventarioVacuno::find($id);
        if (!$inv) {
            return response()->json(['success' => false, 'message' => 'Inventario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Num_Becerra'      => 'nullable|integer|min:0',
            'Num_Mauta'        => 'nullable|integer|min:0',
            'Num_Novilla'      => 'nullable|integer|min:0',
            'Num_Vaca'         => 'nullable|integer|min:0',
            'Num_Becerro'      => 'nullable|integer|min:0',
            'Num_Maute'        => 'nullable|integer|min:0',
            'Num_Torete'       => 'nullable|integer|min:0',
            'Num_Toro'         => 'nullable|integer|min:0',
            'Fecha_Inventario' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $inv->update($request->only([
            'Num_Becerra', 'Num_Mauta', 'Num_Novilla', 'Num_Vaca',
            'Num_Becerro', 'Num_Maute', 'Num_Torete', 'Num_Toro', 'Fecha_Inventario',
        ]));

        return response()->json(['success' => true, 'message' => 'Inventario actualizado', 'data' => $inv]);
    }

    public function destroy($id)
    {
        $inv = InventarioVacuno::find($id);
        if (!$inv) {
            return response()->json(['success' => false, 'message' => 'Inventario no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $inv->delete();
        return response()->json(['success' => true, 'message' => 'Inventario eliminado']);
    }
}
