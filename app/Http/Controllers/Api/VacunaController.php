<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vacuna;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class VacunaController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacuna::with('casasComerciales');

        if ($request->has('nombre')) {
            $query->byNombre($request->nombre);
        }

        if ($request->filled('activa')) {
            $query->where('activa', filter_var($request->activa, FILTER_VALIDATE_BOOLEAN));
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Catálogo de vacunas',
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
            'vacuna_nombre' => 'required|string|max:80',
            'vacuna_descripcion' => 'nullable|string',
            'activa' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vacuna = Vacuna::create($request->only(['vacuna_nombre', 'vacuna_descripcion', 'activa']));

        return response()->json(['success' => true, 'message' => 'Vacuna creada', 'data' => $vacuna], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $vacuna = Vacuna::with('casasComerciales', 'dosis')->find($id);
        if (!$vacuna) {
            return response()->json(['success' => false, 'message' => 'Vacuna no encontrada'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $vacuna]);
    }

    public function update(Request $request, $id)
    {
        $vacuna = Vacuna::find($id);
        if (!$vacuna) {
            return response()->json(['success' => false, 'message' => 'Vacuna no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'vacuna_nombre' => 'sometimes|string|max:80',
            'vacuna_descripcion' => 'nullable|string',
            'activa' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vacuna->update($request->only(['vacuna_nombre', 'vacuna_descripcion', 'activa']));

        return response()->json(['success' => true, 'message' => 'Vacuna actualizada', 'data' => $vacuna]);
    }

    public function destroy($id)
    {
        $vacuna = Vacuna::find($id);
        if (!$vacuna) {
            return response()->json(['success' => false, 'message' => 'Vacuna no encontrada'], Response::HTTP_NOT_FOUND);
        }
        $vacuna->delete();
        return response()->json(['success' => true, 'message' => 'Vacuna eliminada']);
    }
}
