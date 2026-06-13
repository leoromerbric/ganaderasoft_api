<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Dosis;
use App\Models\HistoricoAplicacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class HistoricoAplicacionController extends Controller
{
    public function previewCampana(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ha_dosis_id' => 'required|exists:dosis,dosis_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dosis = Dosis::with(['vacuna', 'casaComercial', 'rebano'])->find($request->integer('ha_dosis_id'));
        if (!$dosis) {
            return response()->json([
                'success' => false,
                'message' => 'Dosis no encontrada',
            ], Response::HTTP_NOT_FOUND);
        }

        $animalIds = $this->resolveAnimalIdsFromDosis($dosis);

        return response()->json([
            'success' => true,
            'message' => 'Previsualización de campaña',
            'data' => [
                'dosis_id' => (int) $dosis->dosis_id,
                'objetivo_tipo' => $dosis->dosis_objetivo_tipo,
                'vacuna' => $dosis->vacuna?->vacuna_nombre,
                'casa_comercial' => $dosis->casaComercial?->laboratorio,
                'rebano' => $dosis->rebano?->Nombre,
                'animales_count' => count($animalIds),
                'animal_ids_sample' => array_slice($animalIds, 0, 25),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = HistoricoAplicacion::with([
            'vacuna',
            'casaComercial',
            'dosis',
            'animal',
            'animal.rebano',
        ]);

        if ($request->filled('vacuna_id')) {
            $query->byVacuna((int) $request->vacuna_id);
        }

        if ($request->filled('dosis_id')) {
            $query->where('ha_dosis_id', (int) $request->dosis_id);
        }

        if ($request->filled('animal_id')) {
            $query->forAnimal((int) $request->animal_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        $records = $query->orderByDesc('id_ha')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Histórico de aplicaciones',
            'data' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ha_vacuna_id' => 'nullable|exists:vacuna,vacuna_id',
            'ha_casa_id' => 'nullable|exists:casa_comercial,casa_id',
            'ha_dosis_id' => 'nullable|exists:dosis,dosis_id',
            'ha_animal_id' => 'nullable|exists:animal,id_Animal',
            'fecha_inyeccion' => 'required|date',
            'observacion' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->filled('ha_dosis_id') && !$request->filled('ha_animal_id')) {
                $validator->errors()->add('ha_animal_id', 'Debe indicar un animal o una dosis objetivo.');
            }

            // When creating a manual record (without dosis), vacuna and casa are mandatory
            // because historico_aplicacion requires both foreign keys.
            if (!$request->filled('ha_dosis_id')) {
                if (!$request->filled('ha_vacuna_id')) {
                    $validator->errors()->add('ha_vacuna_id', 'Debe indicar la vacuna para registro manual.');
                }

                if (!$request->filled('ha_casa_id')) {
                    $validator->errors()->add('ha_casa_id', 'Debe indicar la casa comercial para registro manual.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $created = [];

        if ($request->filled('ha_dosis_id')) {
            $dosis = Dosis::find($request->integer('ha_dosis_id'));

            if (!$dosis) {
                return response()->json(['success' => false, 'message' => 'Dosis no encontrada'], Response::HTTP_NOT_FOUND);
            }

            $animalIds = $this->resolveAnimalIdsFromDosis($dosis);
            if (empty($animalIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La dosis no tiene animales objetivo con los filtros actuales.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            foreach ($animalIds as $animalId) {
                $created[] = HistoricoAplicacion::create([
                    'ha_vacuna_id' => $request->integer('ha_vacuna_id') ?: $dosis->dosis_vacuna_id,
                    'ha_casa_id' => $request->integer('ha_casa_id') ?: $dosis->dosis_casa_id,
                    'ha_dosis_id' => $dosis->dosis_id,
                    'ha_animal_id' => $animalId,
                    'ha_origen_tipo' => $this->mapOrigenTipo($dosis->dosis_objetivo_tipo),
                    'fecha_inyeccion' => $request->input('fecha_inyeccion'),
                    'observacion' => $request->input('observacion'),
                ]);
            }

            $first = $created[0]->load(['vacuna', 'casaComercial', 'dosis', 'animal']);
            return response()->json([
                'success' => true,
                'message' => 'Aplicación registrada',
                'data' => $first,
                'created_count' => count($created),
            ], Response::HTTP_CREATED);
        }

        $ha = HistoricoAplicacion::create([
            'ha_vacuna_id' => $request->integer('ha_vacuna_id'),
            'ha_casa_id' => $request->integer('ha_casa_id'),
            'ha_dosis_id' => $request->integer('ha_dosis_id') ?: null,
            'ha_animal_id' => $request->integer('ha_animal_id'),
            'ha_origen_tipo' => 'manual',
            'fecha_inyeccion' => $request->input('fecha_inyeccion'),
            'observacion' => $request->input('observacion'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aplicación registrada',
            'data' => $ha->load(['vacuna', 'casaComercial', 'dosis', 'animal']),
            'created_count' => 1,
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $ha = HistoricoAplicacion::with(['vacuna', 'casaComercial', 'dosis', 'animal', 'animal.rebano'])->find($id);

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
            'observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ha->update($request->only(['fecha_inyeccion', 'observacion']));

        return response()->json([
            'success' => true,
            'message' => 'Aplicación actualizada',
            'data' => $ha->load(['vacuna', 'casaComercial', 'dosis', 'animal']),
        ]);
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

    private function mapOrigenTipo(string $objetivoTipo): string
    {
        if ($objetivoTipo === 'animal') {
            return 'dosis_animal';
        }

        if ($objetivoTipo === 'rebano') {
            return 'dosis_rebano';
        }

        return 'dosis_subgrupo';
    }

    private function resolveAnimalIdsFromDosis(Dosis $dosis): array
    {
        if ($dosis->dosis_objetivo_tipo === 'animal') {
            return $dosis->dosis_objetivo_animal_id ? [(int) $dosis->dosis_objetivo_animal_id] : [];
        }

        $query = Animal::query();

        if ($dosis->dosis_objetivo_rebano_id) {
            $query->where('id_Rebano', (int) $dosis->dosis_objetivo_rebano_id);
        }

        if ($dosis->dosis_objetivo_tipo === 'subgrupo') {
            $filters = is_array($dosis->dosis_objetivo_filtros) ? $dosis->dosis_objetivo_filtros : [];

            if (!empty($filters['sexo'])) {
                $query->where('Sexo', $filters['sexo']);
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
}
