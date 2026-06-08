<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\MovimientoRebano;
use App\Models\MovimientoRebanoAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MovimientoRebanoController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoRebano::with('fincaOrigen', 'rebanoOrigen', 'fincaDestino', 'rebanoDestino', 'animales.animal');

        if ($request->has('id_finca')) {
            $query->forFinca($request->id_finca);
        }
        if ($request->has('id_rebano')) {
            $query->forRebano($request->id_rebano);
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Movimientos de rebaño',
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
            'id_Finca'          => 'required|exists:finca,id_Finca',
            'id_Rebano'         => 'required|exists:rebano,id_Rebano',
            'Rebano_Destino'    => 'nullable|string|max:30',
            'id_Finca_Destino'  => 'required|exists:finca,id_Finca',
            'id_Rebano_Destino' => 'required|exists:rebano,id_Rebano',
            'Comentario'        => 'nullable|string|max:40',
            'animales'          => 'required|array|min:1',
            'animales.*'        => 'exists:animal,id_Animal',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ((int) $request->id_Finca === (int) $request->id_Finca_Destino) {
            return response()->json(['success' => false, 'message' => 'La finca de destino debe ser diferente a la de origen'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ((int) $request->id_Rebano === (int) $request->id_Rebano_Destino) {
            return response()->json(['success' => false, 'message' => 'El rebaño de destino debe ser diferente al de origen'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animalIds = collect($request->animales)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $animalesOrigenCount = Animal::whereIn('id_Animal', $animalIds)
            ->where('id_Rebano', (int) $request->id_Rebano)
            ->count();

        if ($animalesOrigenCount !== count($animalIds)) {
            return response()->json(['success' => false, 'message' => 'Todos los animales seleccionados deben pertenecer al rebaño de origen'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $movimiento = DB::transaction(function () use ($request) {
            $mov = MovimientoRebano::create($request->only([
                'id_Finca', 'id_Rebano', 'Rebano_Destino',
                'id_Finca_Destino', 'id_Rebano_Destino', 'Comentario',
            ]));

            $animalIds = collect($request->animales)->map(fn ($id) => (int) $id)->unique()->values()->all();
            if (!empty($animalIds)) {
                foreach ($animalIds as $animalId) {
                    MovimientoRebanoAnimal::create([
                        'id_Animal'    => $animalId,
                        'id_Movimiento'=> $mov->id_Movimiento,
                        'Estado'       => 'activo',
                    ]);
                }

                Animal::whereIn('id_Animal', $animalIds)
                    ->update(['id_Rebano' => (int) $request->id_Rebano_Destino]);
            }

            return $mov;
        });

        return response()->json([
            'success' => true,
            'message' => 'Movimiento registrado',
            'data'    => $movimiento->load('animales'),
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $movimiento = MovimientoRebano::with('fincaOrigen', 'rebanoOrigen', 'fincaDestino', 'rebanoDestino', 'animales.animal')->find($id);
        if (!$movimiento) {
            return response()->json(['success' => false, 'message' => 'Movimiento no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $movimiento]);
    }

    public function update(Request $request, $id)
    {
        $movimiento = MovimientoRebano::find($id);
        if (!$movimiento) {
            return response()->json(['success' => false, 'message' => 'Movimiento no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Rebano_Destino' => 'nullable|string|max:30',
            'Comentario'     => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $movimiento->update($request->only(['Rebano_Destino', 'Comentario']));

        return response()->json(['success' => true, 'message' => 'Movimiento actualizado', 'data' => $movimiento]);
    }

    public function destroy($id)
    {
        $movimiento = MovimientoRebano::find($id);
        if (!$movimiento) {
            return response()->json(['success' => false, 'message' => 'Movimiento no encontrado'], Response::HTTP_NOT_FOUND);
        }
        DB::transaction(function () use ($movimiento) {
            MovimientoRebanoAnimal::where('id_Movimiento', $movimiento->id_Movimiento)->delete();
            $movimiento->delete();
        });
        return response()->json(['success' => true, 'message' => 'Movimiento eliminado']);
    }
}
