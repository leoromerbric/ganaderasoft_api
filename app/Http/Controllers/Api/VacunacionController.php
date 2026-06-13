<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Vacunacion;
use App\Models\VacunacionAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VacunacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacunacion::with(['vacuna', 'rebano'])
            ->withCount('animales as animales_count');

        if ($request->filled('vacuna_id')) {
            $query->forVacuna((int) $request->vacuna_id);
        }

        if ($request->filled('rebano_id')) {
            $query->forRebano((int) $request->rebano_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->where('vacunacion_fecha', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->where('vacunacion_fecha', '<=', $request->input('fecha_fin'));
        }

        $records = $query->orderByDesc('vacunacion_id')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Vacunaciones',
            'data' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Lista de animales elegibles para vacunar segun filtros de rebano, sexo y etapa.
     */
    public function animalesElegibles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rebano_id' => 'required|exists:rebano,id_Rebano',
            'sexo' => 'nullable|in:M,H',
            'etapa_id' => 'nullable|integer|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animales = $this->eligibleAnimalsQuery(
            (int) $request->input('rebano_id'),
            $request->input('sexo'),
            $request->filled('etapa_id') ? (int) $request->input('etapa_id') : null
        )
            ->orderBy('Nombre')
            ->get(['id_Animal', 'id_Rebano', 'Nombre', 'codigo_animal', 'Sexo']);

        return response()->json([
            'success' => true,
            'message' => 'Animales elegibles',
            'data' => $animales,
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animalIds = $this->confirmedAnimalIds($request->all());
        if (empty($animalIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe seleccionar al menos un animal para vacunar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vacunacion = DB::transaction(function () use ($request, $animalIds) {
            $costo = (float) $request->input('vacunacion_costo_dosis');

            $vacunacion = Vacunacion::create([
                'vacunacion_vacuna_id' => (int) $request->input('vacunacion_vacuna_id'),
                'vacunacion_casa_id' => null,
                'vacunacion_rebano_id' => (int) $request->input('vacunacion_rebano_id'),
                'vacunacion_modo_seleccion' => 'lista_animales',
                'vacunacion_filtros' => $request->input('vacunacion_filtros'),
                'vacunacion_fecha' => $request->input('vacunacion_fecha'),
                'vacunacion_costo_dosis' => $costo,
                'vacunacion_total_animales' => count($animalIds),
                'vacunacion_monto_total' => round(count($animalIds) * $costo, 2),
                'vacunacion_observacion' => $request->input('vacunacion_observacion'),
            ]);

            $this->syncAnimales($vacunacion->vacunacion_id, $animalIds);

            return $vacunacion;
        });

        return response()->json([
            'success' => true,
            'message' => 'Vacunación registrada',
            'data' => $this->loadVacunacion($vacunacion->vacunacion_id),
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $vacunacion = $this->loadVacunacion((int) $id);

        if (!$vacunacion) {
            return response()->json(['success' => false, 'message' => 'Vacunación no encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['success' => true, 'data' => $vacunacion]);
    }

    public function update(Request $request, $id)
    {
        $vacunacion = Vacunacion::find((int) $id);

        if (!$vacunacion) {
            return response()->json(['success' => false, 'message' => 'Vacunación no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animalIds = $this->confirmedAnimalIds($request->all());
        if (empty($animalIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe seleccionar al menos un animal para vacunar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($request, $vacunacion, $animalIds) {
            $costo = (float) $request->input('vacunacion_costo_dosis');

            $vacunacion->update([
                'vacunacion_vacuna_id' => (int) $request->input('vacunacion_vacuna_id'),
                'vacunacion_casa_id' => null,
                'vacunacion_rebano_id' => (int) $request->input('vacunacion_rebano_id'),
                'vacunacion_modo_seleccion' => 'lista_animales',
                'vacunacion_filtros' => $request->input('vacunacion_filtros'),
                'vacunacion_fecha' => $request->input('vacunacion_fecha'),
                'vacunacion_costo_dosis' => $costo,
                'vacunacion_total_animales' => count($animalIds),
                'vacunacion_monto_total' => round(count($animalIds) * $costo, 2),
                'vacunacion_observacion' => $request->input('vacunacion_observacion'),
            ]);

            VacunacionAnimal::where('va_vacunacion_id', $vacunacion->vacunacion_id)->delete();
            $this->syncAnimales($vacunacion->vacunacion_id, $animalIds);
        });

        return response()->json([
            'success' => true,
            'message' => 'Vacunación actualizada',
            'data' => $this->loadVacunacion($vacunacion->vacunacion_id),
        ]);
    }

    public function destroy($id)
    {
        $vacunacion = Vacunacion::find((int) $id);

        if (!$vacunacion) {
            return response()->json(['success' => false, 'message' => 'Vacunación no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $vacunacion->delete();

        return response()->json(['success' => true, 'message' => 'Vacunación eliminada']);
    }

    private function validator(array $data)
    {
        return Validator::make($data, [
            'vacunacion_vacuna_id' => 'required|exists:vacuna,vacuna_id',
            'vacunacion_rebano_id' => 'required|exists:rebano,id_Rebano',
            'vacunacion_animal_ids' => 'required|array|min:1',
            'vacunacion_animal_ids.*' => 'integer|exists:animal,id_Animal',
            'vacunacion_filtros' => 'nullable|array',
            'vacunacion_filtros.sexo' => 'nullable|in:M,H',
            'vacunacion_filtros.etapa_id' => 'nullable|integer|exists:etapa,etapa_id',
            'vacunacion_costo_dosis' => 'required|numeric|min:0',
            'vacunacion_fecha' => 'required|date',
            'vacunacion_observacion' => 'nullable|string',
        ]);
    }

    /**
     * Solo se guardan los animales marcados que realmente pertenecen al rebano indicado.
     */
    private function confirmedAnimalIds(array $data): array
    {
        $rebanoId = (int) ($data['vacunacion_rebano_id'] ?? 0);

        $ids = collect($data['vacunacion_animal_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return Animal::query()
            ->where('id_Rebano', $rebanoId)
            ->whereIn('id_Animal', $ids)
            ->pluck('id_Animal')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function eligibleAnimalsQuery(int $rebanoId, ?string $sexo, ?int $etapaId)
    {
        $query = Animal::query()->where('id_Rebano', $rebanoId);

        if (method_exists(Animal::class, 'scopeActive')) {
            $query->active();
        }

        if (!empty($sexo)) {
            $query->where('Sexo', $sexo);
        }

        if (!empty($etapaId)) {
            $query->whereHas('etapaAnimales', function ($q) use ($etapaId) {
                $q->where('etan_etapa_id', $etapaId)
                    ->where(function ($sq) {
                        $sq->whereNull('etan_fecha_fin')
                            ->orWhere('etan_fecha_fin', '>', now()->toDateString());
                    });
            });
        }

        return $query;
    }

    private function syncAnimales(int $vacunacionId, array $animalIds): void
    {
        $rows = collect($animalIds)->map(fn ($animalId) => [
            'va_vacunacion_id' => $vacunacionId,
            'va_animal_id' => $animalId,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        VacunacionAnimal::insert($rows);
    }

    private function loadVacunacion(int $id): ?Vacunacion
    {
        return Vacunacion::with([
            'vacuna',
            'rebano',
            'animales.animal',
        ])->withCount('animales as animales_count')->find($id);
    }
}
