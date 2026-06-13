<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\ArbolGen;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ArbolGenController extends Controller
{
    /**
     * Devuelve el árbol genealógico completo de un animal (3 generaciones):
     * abuelos paternos/maternos → padre/madre → animal → hijos directos.
     */
    public function arbol(Animal $animal)
    {
        $animal->load([
            'registroPadre.progenitor.registroPadre.progenitor',
            'registroPadre.progenitor.registroMadre.progenitor',
            'registroMadre.progenitor.registroPadre.progenitor',
            'registroMadre.progenitor.registroMadre.progenitor',
            'hijos.hijo',
        ]);

        $padre = optional($animal->registroPadre)->progenitor;
        $madre = optional($animal->registroMadre)->progenitor;

        $abueloPaterno  = $padre ? optional($padre->registroPadre)->progenitor : null;
        $abuelaPaterna  = $padre ? optional($padre->registroMadre)->progenitor : null;
        $abueloMaterno  = $madre ? optional($madre->registroPadre)->progenitor : null;
        $abuelaMaterna  = $madre ? optional($madre->registroMadre)->progenitor : null;

        $data = [
            'animal' => $this->animalBasic($animal),
            'padre'  => $padre ? array_merge($this->animalBasic($padre), [
                'abuelo_paterno' => $abueloPaterno ? $this->animalBasic($abueloPaterno) : null,
                'abuela_paterna' => $abuelaPaterna ? $this->animalBasic($abuelaPaterna) : null,
            ]) : null,
            'madre'  => $madre ? array_merge($this->animalBasic($madre), [
                'abuelo_materno' => $abueloMaterno ? $this->animalBasic($abueloMaterno) : null,
                'abuela_materna' => $abuelaMaterna ? $this->animalBasic($abuelaMaterna) : null,
            ]) : null,
            'hijos'  => $animal->hijos->map(function ($rel) {
                return $rel->hijo ? $this->animalBasic($rel->hijo) : null;
            })->filter()->values(),
            'relaciones' => [
                'id_arbol_padre' => optional($animal->registroPadre)->id_arbol,
                'id_arbol_madre' => optional($animal->registroMadre)->id_arbol,
            ],
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Registra o actualiza la relación padre/madre de un animal.
     * Body: { tipo: 'Padre'|'Madre', id_padre: int }
     */
    public function setProgenitor(Request $request, Animal $animal)
    {
        $validator = Validator::make($request->all(), [
            'tipo'     => 'required|in:Padre,Madre',
            'id_padre' => [
                'required',
                'integer',
                'exists:animal,id_Animal',
                function ($attr, $value, $fail) use ($animal) {
                    if ($value == $animal->id_Animal) {
                        $fail('Un animal no puede ser su propio progenitor.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validar sexo coherente con el tipo
        $progenitor = Animal::find($request->id_padre);
        if ($request->tipo === 'Padre' && $progenitor->Sexo === 'F') {
            return response()->json(['success' => false, 'message' => 'El Padre debe ser un animal macho (M).'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($request->tipo === 'Madre' && $progenitor->Sexo === 'M') {
            return response()->json(['success' => false, 'message' => 'La Madre debe ser un animal hembra (H).'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $relacion = ArbolGen::updateOrCreate(
            ['id_hijo' => $animal->id_Animal, 'tipo' => $request->tipo],
            ['id_padre' => (int) $request->id_padre]
        );

        return response()->json([
            'success' => true,
            'message' => "Relación de {$request->tipo} guardada correctamente.",
            'data'    => $relacion,
        ], Response::HTTP_OK);
    }

    /**
     * Elimina la relación padre o madre de un animal.
     * Route param: tipo = 'Padre'|'Madre'
     */
    public function removeProgenitor(Animal $animal, string $tipo)
    {
        if (!in_array($tipo, ['Padre', 'Madre'])) {
            return response()->json(['success' => false, 'message' => 'Tipo inválido. Use Padre o Madre.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $deleted = ArbolGen::where('id_hijo', $animal->id_Animal)
            ->where('tipo', $tipo)
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'No se encontró la relación a eliminar.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['success' => true, 'message' => "Relación de {$tipo} eliminada."]);
    }

    /**
     * Lista animales disponibles para asignar como progenitor (filtra por sexo coherente).
     */
    public function disponibles(Request $request, Animal $animal)
    {
        $tipo = $request->query('tipo');
        $query = Animal::where('archivado', false)
            ->where('id_Animal', '!=', $animal->id_Animal);

        if ($tipo === 'Padre') {
            $query->where('Sexo', 'M');
        } elseif ($tipo === 'Madre') {
            $query->where('Sexo', 'F');
        }

        $animales = $query->orderBy('Nombre')
            ->get(['id_Animal', 'Nombre', 'codigo_animal', 'Sexo']);

        return response()->json(['success' => true, 'data' => $animales]);
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function animalBasic(Animal $a): array
    {
        return [
            'id_Animal'     => $a->id_Animal,
            'Nombre'        => $a->Nombre,
            'codigo_animal' => $a->codigo_animal,
            'Sexo'          => $a->Sexo,
            'fecha_nacimiento' => $a->fecha_nacimiento?->format('Y-m-d'),
        ];
    }
}
