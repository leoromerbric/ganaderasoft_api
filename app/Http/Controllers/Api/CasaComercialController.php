<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasaComercial;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CasaComercialController extends Controller
{
    public function index(Request $request)
    {
        $query = CasaComercial::query();

        if ($request->has('laboratorio')) {
            $query->byLaboratorio($request->laboratorio);
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Casas comerciales',
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
            'laboratorio'    => 'required|string|max:30',
            'marca_comercial'=> 'required|string|max:25',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $casa = CasaComercial::create($request->only(['laboratorio', 'marca_comercial']));

        return response()->json(['success' => true, 'message' => 'Casa comercial creada', 'data' => $casa], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $casa = CasaComercial::with('vacunas', 'dosis')->find($id);
        if (!$casa) {
            return response()->json(['success' => false, 'message' => 'Casa comercial no encontrada'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $casa]);
    }

    public function update(Request $request, $id)
    {
        $casa = CasaComercial::find($id);
        if (!$casa) {
            return response()->json(['success' => false, 'message' => 'Casa comercial no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'laboratorio'    => 'sometimes|string|max:30',
            'marca_comercial'=> 'sometimes|string|max:25',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $casa->update($request->only(['laboratorio', 'marca_comercial']));

        return response()->json(['success' => true, 'message' => 'Casa comercial actualizada', 'data' => $casa]);
    }

    public function destroy($id)
    {
        $casa = CasaComercial::find($id);
        if (!$casa) {
            return response()->json(['success' => false, 'message' => 'Casa comercial no encontrada'], Response::HTTP_NOT_FOUND);
        }
        $casa->delete();
        return response()->json(['success' => true, 'message' => 'Casa comercial eliminada']);
    }
}
