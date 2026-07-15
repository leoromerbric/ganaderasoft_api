<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\Rebano;
use App\Models\EstadoAnimal;
use App\Models\EtapaAnimal;
use App\Models\PesoCorporal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class AnimalService
{
    /**
     * Constructor del servicio.
     * Inyecta el clasificador de etapas para la sincronización automática de etapa por edad y peso.
     *
     * @param EtapaClassifierService $etapaClassifier
     */
    public function __construct(
        private EtapaClassifierService $etapaClassifier
    ) {}

    /**
     * Obtiene el listado de animales activos aplicando filtros y verificando permisos del usuario.
     *
     * @param array $filters Filtros aplicados (rebano_id, sexo, etc.).
     * @param mixed $user Usuario que realiza la petición.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listAnimals(array $filters, $user): LengthAwarePaginator
    {
        $query = Animal::with(['rebano.finca.propietario', 'composicionRaza'])
            ->active();

        // Aplicamos los filtros básicos si existen en la petición
        if (!empty($filters['rebano_id'])) {
            $query->forRebano($filters['rebano_id']);
        }

        if (!empty($filters['sexo'])) {
            $query->bySex($filters['sexo']);
        }

        // Si el usuario es administrador, puede ver todos los animales
        if ($user->isAdmin()) {
            return $query->paginate(15);
        }

        // Si es propietario, restringimos la búsqueda solo a los animales de sus fincas
        $propietario = $user->propietario;
        if (!$propietario) {
            throw new AuthorizationException('El usuario no está registrado como propietario.');
        }

        return $query->whereHas('rebano.finca', function ($q) use ($propietario) {
            $q->where('propietario_id', $propietario->id);
        })->paginate(15);
    }

    /**
     * Guarda un nuevo animal y inicializa sus estados y etapas si se proveen.
     *
     * @param array $data Datos del animal y estados/etapas iniciales.
     * @param mixed $user Usuario que realiza la petición.
     * @return array Contiene el modelo del Animal creado y los metadatos de clasificación.
     * @throws AuthorizationException
     */
    public function storeAnimal(array $data, $user): array
    {
        // Validación de permisos para no administradores
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                throw new AuthorizationException('El usuario no está registrado como propietario.');
            }

            $rebano = Rebano::with('finca')->find($data['id_Rebano']);
            if (!$rebano || $rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para agregar un animal a este rebaño.');
            }
        }

        // Creación del animal mapeando los campos del request a las columnas actuales de la DB
        $animal = Animal::create([
            'rebano_id'           => $data['id_Rebano'],
            'nombre'              => $data['Nombre'] ?? null,
            'codigo_animal'       => $data['codigo_animal'] ?? null,
            'sexo'                => $data['Sexo'],
            'fecha_nacimiento'    => $data['fecha_nacimiento'],
            'procedencia'         => $data['Procedencia'] ?? null,
            'composicion_raza_id' => $data['fk_composicion_raza'],
            'archivado'           => false
        ]);

        // Guarda el estado de salud inicial si fue enviado
        if (!empty($data['estado_inicial'])) {
            EstadoAnimal::create([
                'fecha_ini'       => $data['estado_inicial']['fecha_ini'],
                'fecha_fin'       => null,
                'estado_salud_id' => $data['estado_inicial']['estado_id'],
                'animal_id'       => $animal->id,
            ]);
        }

        // Guarda la etapa inicial si fue enviada
        if (!empty($data['etapa_inicial'])) {
            EtapaAnimal::create([
                'fecha_ini' => $data['etapa_inicial']['fecha_ini'],
                'fecha_fin' => null,
                'animal_id' => $animal->id,
                'etapa_id'  => $data['etapa_inicial']['etapa_id'],
            ]);
        }

        // Ejecuta la clasificación y sincronización automática de etapa
        $lastWeight = PesoCorporal::where('animal_etapa_id', $animal->id)
            ->orderByDesc('fecha_peso')
            ->value('peso');

        $clasificacion = $this->etapaClassifier->syncCurrentEtapa($animal, $lastWeight !== null ? (float) $lastWeight : null);

        return [$animal, $clasificacion];
    }

    /**
     * Retorna los detalles de un animal específico validando permisos de acceso.
     *
     * @param int $id ID del animal.
     * @param mixed $user Usuario que realiza la petición.
     * @return Animal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getAnimal(int $id, $user): Animal
    {
        $animal = Animal::with([
            'rebano.finca.propietario',
            'estados.estadoSalud',
            'etapaAnimales.etapa',
            'etapaActual.etapa'
        ])->findOrFail($id);

        // Control de acceso para no administradores
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para ver este animal.');
            }
        }

        return $animal;
    }

    /**
     * Actualiza los datos de un animal y recalcula su etapa de crecimiento.
     *
     * @param int $id ID del animal.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return array Contiene el modelo actualizado y los metadatos de clasificación.
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateAnimal(int $id, array $data, $user): array
    {
        $animal = Animal::findOrFail($id);

        // Validación de permisos
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para actualizar este animal.');
            }

            // Si intenta cambiar de rebaño, valida pertenencia en el nuevo rebaño
            if (!empty($data['id_Rebano'])) {
                $newRebano = Rebano::with('finca')->find($data['id_Rebano']);
                if (!$newRebano || $newRebano->finca->propietario_id != $propietario->id) {
                    throw new AuthorizationException('No tiene permisos para mover el animal a ese rebaño.');
                }
            }
        }

        // Mapeo selectivo de campos para la actualización
        $updatePayload = [];
        if (array_key_exists('id_Rebano', $data)) $updatePayload['rebano_id'] = $data['id_Rebano'];
        if (array_key_exists('Nombre', $data)) $updatePayload['nombre'] = $data['Nombre'];
        if (array_key_exists('codigo_animal', $data)) $updatePayload['codigo_animal'] = $data['codigo_animal'];
        if (array_key_exists('Sexo', $data)) $updatePayload['sexo'] = $data['Sexo'];
        if (array_key_exists('fecha_nacimiento', $data)) $updatePayload['fecha_nacimiento'] = $data['fecha_nacimiento'];
        if (array_key_exists('Procedencia', $data)) $updatePayload['procedencia'] = $data['Procedencia'];
        if (array_key_exists('fk_composicion_raza', $data)) $updatePayload['composicion_raza_id'] = $data['fk_composicion_raza'];

        $animal->update($updatePayload);

        // Recalculamos la clasificación tras la actualización
        $lastWeight = PesoCorporal::where('animal_etapa_id', $animal->id)
            ->orderByDesc('fecha_peso')
            ->value('peso');

        $clasificacion = $this->etapaClassifier->syncCurrentEtapa($animal, $lastWeight !== null ? (float) $lastWeight : null);

        return [$animal, $clasificacion];
    }

    /**
     * Realiza un borrado lógico (archivado = true) del animal.
     *
     * @param int $id ID del animal.
     * @param mixed $user Usuario.
     * @return void
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function archiveAnimal(int $id, $user): void
    {
        $animal = Animal::findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para archivar este animal.');
            }
        }

        $animal->update(['archivado' => true]);
    }
}
