<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Vacunacion;
use App\Models\VacunacionAnimal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VacunacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacunacion::with(['vacuna', 'casaComercial', 'rebano'])
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

    public function preview(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animalIds = $this->resolveAnimalIds($request->all());
        if (empty($animalIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay animales elegibles con la selección indicada.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $costo = (float) $request->input('vacunacion_costo_dosis');

        return response()->json([
            'success' => true,
            'message' => 'Previsualización de vacunación',
            'data' => [
                'animales_count' => count($animalIds),
                'monto_total' => round(count($animalIds) * $costo, 2),
                'animal_ids_sample' => array_slice($animalIds, 0, 50),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animalIds = $this->resolveAnimalIds($request->all());
        if (empty($animalIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay animales elegibles con la selección indicada.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vacunacion = DB::transaction(function () use ($request, $animalIds) {
            $costo = (float) $request->input('vacunacion_costo_dosis');

            $vacunacion = Vacunacion::create([
                'vacunacion_vacuna_id' => (int) $request->input('vacunacion_vacuna_id'),
                'vacunacion_casa_id' => $request->filled('vacunacion_casa_id') ? (int) $request->input('vacunacion_casa_id') : null,
                'vacunacion_rebano_id' => (int) $request->input('vacunacion_rebano_id'),
                'vacunacion_modo_seleccion' => $request->input('vacunacion_modo_seleccion'),
                'vacunacion_filtros' => $request->input('vacunacion_filtros'),
                'vacunacion_fecha' => $request->input('vacunacion_fecha'),
                'vacunacion_costo_dosis' => $costo,
                'vacunacion_total_animales' => count($animalIds),
                'vacunacion_monto_total' => round(count($animalIds) * $costo, 2),
                'vacunacion_observacion' => $request->input('vacunacion_observacion'),
            ]);

            $rows = collect($animalIds)->map(fn ($animalId) => [
                'va_vacunacion_id' => $vacunacion->vacunacion_id,
                'va_animal_id' => $animalId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            VacunacionAnimal::insert($rows);

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

        $animalIds = $this->resolveAnimalIds($request->all());
        if (empty($animalIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay animales elegibles con la selección indicada.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($request, $vacunacion, $animalIds) {
            $costo = (float) $request->input('vacunacion_costo_dosis');

            $vacunacion->update([
                'vacunacion_vacuna_id' => (int) $request->input('vacunacion_vacuna_id'),
                'vacunacion_casa_id' => $request->filled('vacunacion_casa_id') ? (int) $request->input('vacunacion_casa_id') : null,
                'vacunacion_rebano_id' => (int) $request->input('vacunacion_rebano_id'),
                'vacunacion_modo_seleccion' => $request->input('vacunacion_modo_seleccion'),
                'vacunacion_filtros' => $request->input('vacunacion_filtros'),
                'vacunacion_fecha' => $request->input('vacunacion_fecha'),
                'vacunacion_costo_dosis' => $costo,
                'vacunacion_total_animales' => count($animalIds),
                'vacunacion_monto_total' => round(count($animalIds) * $costo, 2),
                'vacunacion_observacion' => $request->input('vacunacion_observacion'),
            ]);

            VacunacionAnimal::where('va_vacunacion_id', $vacunacion->vacunacion_id)->delete();

            $rows = collect($animalIds)->map(fn ($animalId) => [
                'va_vacunacion_id' => $vacunacion->vacunacion_id,
                'va_animal_id' => $animalId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            VacunacionAnimal::insert($rows);
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
        $validator = Validator::make($data, [
            'vacunacion_vacuna_id' => 'required|exists:vacuna,vacuna_id',
            'vacunacion_casa_id' => 'nullable|exists:casa_comercial,casa_id',
            'vacunacion_rebano_id' => 'required|exists:rebano,id_Rebano',
            'vacunacion_modo_seleccion' => ['required', Rule::in(['todos_rebano', 'lista_animales', 'filtros'])],
            'vacunacion_animal_ids' => 'nullable|array',
            'vacunacion_animal_ids.*' => 'integer|exists:animal,id_Animal',
            'vacunacion_filtros' => 'nullable|array',
            'vacunacion_costo_dosis' => 'required|numeric|min:0',
            'vacunacion_fecha' => 'required|date',
            'vacunacion_observacion' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($data) {
            $modo = $data['vacunacion_modo_seleccion'] ?? null;

            if ($modo === 'lista_animales' && empty($data['vacunacion_animal_ids'])) {
                $validator->errors()->add('vacunacion_animal_ids', 'Debe seleccionar al menos un animal.');
            }
        });

        return $validator;
    }

    private function resolveAnimalIds(array $data): array
    {
        $modo = $data['vacunacion_modo_seleccion'];
        $rebanoId = (int) $data['vacunacion_rebano_id'];

        if ($modo === 'lista_animales') {
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

        $query = Animal::query()->where('id_Rebano', $rebanoId);

        if ($modo === 'filtros') {
            $filters = is_array($data['vacunacion_filtros'] ?? null) ? $data['vacunacion_filtros'] : [];

            if (!empty($filters['sexo'])) {
                $query->where('Sexo', $filters['sexo']);
            }

            if (!empty($filters['nombre_like'])) {
                $query->where('Nombre', 'like', '%' . $filters['nombre_like'] . '%');
            }

            if (!empty($filters['codigo_like'])) {
                $query->where('codigo_animal', 'like', '%' . $filters['codigo_like'] . '%');
            }

            if (!empty($filters['edad_min_dias']) || !empty($filters['edad_max_dias'])) {
                $today = Carbon::today();
                if (!empty($filters['edad_min_dias'])) {
                    $maxBirthDate = $today->copy()->subDays((int) $filters['edad_min_dias'])->toDateString();
                    $query->where('fecha_nacimiento', '<=', $maxBirthDate);
                }
                if (!empty($filters['edad_max_dias'])) {
                    $minBirthDate = $today->copy()->subDays((int) $filters['edad_max_dias'])->toDateString();
                    $query->where('fecha_nacimiento', '>=', $minBirthDate);
                }
            }

            if (!empty($filters['etapa_id'])) {
                $etapaId = (int) $filters['etapa_id'];
                $query->whereHas('etapaAnimales', function ($q) use ($etapaId) {
                    $q->where('etan_etapa_id', $etapaId)
                        ->where(function ($sq) {
                            $sq->whereNull('etan_fecha_fin')
                                ->orWhere('etan_fecha_fin', '>', now()->toDateString());
                        });
                });
            }
        }

        return $query->pluck('id_Animal')->map(fn ($id) => (int) $id)->values()->all();
    }

    private function loadVacunacion(int $id): ?Vacunacion
    {
        return Vacunacion::with([
            'vacuna',
            'casaComercial',
            'rebano',
            'animales.animal',
        ])->withCount('animales as animales_count')->find($id);
    }
}
