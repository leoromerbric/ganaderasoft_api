<?php

namespace App\Services\Rebano;

use App\Models\MovimientoRebano;
use App\Models\MovimientoRebanoAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class MovimientoRebanoService
{
    /**
     * Obtiene la lista de movimientos de rebaño con filtros.
     *
     * @param array $filters Filtros.
     * @return LengthAwarePaginator
     */
    public function listMovimientos(array $filters)
    {
        $query = MovimientoRebano::with([
            'fincaOrigen', 'rebanoOrigen',
            'fincaDestino', 'rebanoDestino',
            'animales.animal'
        ]);

        if (!empty($filters['finca_id'])) {
            $query->forFinca($filters['finca_id']);
        }

        if (!empty($filters['rebano_id'])) {
            $query->forRebano($filters['rebano_id']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo movimiento de rebaño y actualiza la ubicación de los animales en una transacción.
     *
     * @param array $data Datos.
     * @return MovimientoRebano
     * @throws UnprocessableEntityHttpException
     */
    public function createMovimiento(array $data): MovimientoRebano
    {
        if ((int) $data['finca_id'] === (int) $data['finca_destino_id']) {
            throw new UnprocessableEntityHttpException('La finca de destino debe ser diferente a la de origen.');
        }

        if ((int) $data['rebano_id'] === (int) $data['rebano_destino_id']) {
            throw new UnprocessableEntityHttpException('El rebaño de destino debe ser diferente al de origen.');
        }

        $animalIds = collect($data['animales'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        
        // Validar que todos los animales pertenezcan al rebaño de origen
        $animalesOrigenCount = Animal::whereIn('id', $animalIds)
            ->where('rebano_id', (int) $data['rebano_id'])
            ->count();

        if ($animalesOrigenCount !== count($animalIds)) {
            throw new UnprocessableEntityHttpException('Todos los animales seleccionados deben pertenecer al rebaño de origen.');
        }

        return DB::transaction(function () use ($data, $animalIds) {
            $mov = MovimientoRebano::create([
                'finca_id'          => $data['finca_id'],
                'rebano_id'         => $data['rebano_id'],
                'rebano_destino'    => $data['rebano_destino'] ?? null,
                'finca_destino_id'  => $data['finca_destino_id'],
                'rebano_destino_id' => $data['rebano_destino_id'],
                'comentario'        => $data['comentario'] ?? null,
            ]);

            foreach ($animalIds as $animalId) {
                MovimientoRebanoAnimal::create([
                    'animal_id'            => $animalId,
                    'movimiento_rebano_id' => $mov->id,
                    'estado'               => 'activo',
                ]);
            }

            // Actualizar la relación de rebaño del animal en V2
            Animal::whereIn('id', $animalIds)
                ->update(['rebano_id' => (int) $data['rebano_destino_id']]);

            return $mov;
        });
    }

    /**
     * Obtiene el detalle de un movimiento específico.
     *
     * @param int $id ID.
     * @return MovimientoRebano
     * @throws ModelNotFoundException
     */
    public function getMovimientoById(int $id): MovimientoRebano
    {
        return MovimientoRebano::with([
            'fincaOrigen', 'rebanoOrigen',
            'fincaDestino', 'rebanoDestino',
            'animales.animal'
        ])->findOrFail($id);
    }

    /**
     * Actualiza un movimiento de rebaño.
     *
     * @param int $id ID.
     * @param array $data Datos.
     * @return MovimientoRebano
     * @throws ModelNotFoundException
     */
    public function updateMovimiento(int $id, array $data): MovimientoRebano
    {
        $movimiento = MovimientoRebano::findOrFail($id);

        $payload = [];
        if (array_key_exists('rebano_destino', $data)) $payload['rebano_destino'] = $data['rebano_destino'];
        if (array_key_exists('comentario', $data)) $payload['comentario'] = $data['comentario'];

        $movimiento->update($payload);

        return $movimiento;
    }

    /**
     * Elimina un movimiento de rebaño.
     *
     * @param int $id ID.
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteMovimiento(int $id): bool
    {
        $movimiento = MovimientoRebano::findOrFail($id);

        DB::transaction(function () use ($movimiento) {
            // Obtener los IDs de los animales que participaron en este movimiento
            $animalIds = MovimientoRebanoAnimal::where('movimiento_rebano_id', $movimiento->id)
                ->pluck('animal_id')
                ->all();

            // Revertir los animales al rebaño de origen
            if (!empty($animalIds)) {
                Animal::whereIn('id', $animalIds)
                    ->update(['rebano_id' => $movimiento->rebano_id]);
            }

            // Eliminar los registros pivot y el movimiento
            MovimientoRebanoAnimal::where('movimiento_rebano_id', $movimiento->id)->delete();
            $movimiento->delete();
        });

        return true;
    }
}
