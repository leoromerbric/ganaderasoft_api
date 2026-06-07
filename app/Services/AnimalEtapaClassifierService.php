<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnimalEtapaClassifierService
{
    public function syncCurrentEtapa(Animal $animal, ?float $latestWeight = null): array
    {
        $animal->loadMissing('composicionRaza');

        $ageDays = Carbon::parse($animal->fecha_nacimiento)->startOfDay()->diffInDays(now()->startOfDay());
        $normalizedSex = $this->normalizeSex($animal->Sexo);
        $tipoAnimalId = $animal->composicionRaza?->fk_tipo_animal_id;

        $targetEtapa = $this->resolveTargetEtapa($tipoAnimalId, $normalizedSex, $ageDays, $latestWeight);

        if (!$targetEtapa) {
            return [
                'changed' => false,
                'age_days' => $ageDays,
                'target_etapa_id' => null,
                'target_etapa' => null,
                'reason' => 'No se pudo determinar la etapa para los datos actuales',
                'weight_check' => $this->buildWeightCheck($ageDays, $latestWeight),
            ];
        }

        $today = now()->toDateString();

        $changed = DB::transaction(function () use ($animal, $targetEtapa, $today) {
            $active = EtapaAnimal::where('etan_animal_id', $animal->id_Animal)
                ->where(function ($q) use ($today) {
                    $q->whereNull('etan_fecha_fin')
                        ->orWhere('etan_fecha_fin', '>', $today);
                })
                ->orderByDesc('etan_fecha_ini')
                ->first();

            if ($active && (int) $active->etan_etapa_id === (int) $targetEtapa->etapa_id) {
                return false;
            }

            EtapaAnimal::where('etan_animal_id', $animal->id_Animal)
                ->where(function ($q) use ($today) {
                    $q->whereNull('etan_fecha_fin')
                        ->orWhere('etan_fecha_fin', '>', $today);
                })
                ->update(['etan_fecha_fin' => $today]);

            $existingTarget = EtapaAnimal::where('etan_animal_id', $animal->id_Animal)
                ->where('etan_etapa_id', $targetEtapa->etapa_id)
                ->first();

            if ($existingTarget) {
                $existingTarget->update([
                    'etan_fecha_ini' => $today,
                    'etan_fecha_fin' => null,
                ]);
            } else {
                EtapaAnimal::create([
                    'etan_animal_id' => $animal->id_Animal,
                    'etan_etapa_id' => $targetEtapa->etapa_id,
                    'etan_fecha_ini' => $today,
                    'etan_fecha_fin' => null,
                ]);
            }

            return true;
        });

        return [
            'changed' => $changed,
            'age_days' => $ageDays,
            'target_etapa_id' => $targetEtapa->etapa_id,
            'target_etapa' => $targetEtapa->etapa_nombre,
            'reason' => null,
            'weight_check' => $this->buildWeightCheck($ageDays, $latestWeight),
        ];
    }

    private function resolveTargetEtapa(?int $tipoAnimalId, string $sex, int $ageDays, ?float $latestWeight): ?Etapa
    {
        if ($tipoAnimalId === 3) {
            $vacunoEtapa = $this->resolveVacunoEtapa($sex, $ageDays, $latestWeight);
            if ($vacunoEtapa) {
                return $vacunoEtapa;
            }
        }

        if (!$tipoAnimalId) {
            return null;
        }

        $sexValues = $sex === 'F' ? ['F', 'H'] : ['M'];

        $candidates = Etapa::query()
            ->forTipoAnimal($tipoAnimalId)
            ->whereIn('etapa_sexo', $sexValues)
            ->orderBy('etapa_edad_ini')
            ->get();

        return $candidates->first(function (Etapa $etapa) use ($ageDays) {
            return $etapa->includesAge($ageDays);
        });
    }

    private function resolveVacunoEtapa(string $sex, int $ageDays, ?float $latestWeight): ?Etapa
    {
        if ($ageDays <= 180) {
            $names = $sex === 'M' ? ['becerro', 'ternero'] : ['becerra', 'ternera'];
            return $this->findVacunoEtapaByNames($names, $sex);
        }

        if ($ageDays <= 548) {
            $names = $sex === 'M' ? ['maute'] : ['mauta'];
            return $this->findVacunoEtapaByNames($names, $sex);
        }

        if ($latestWeight !== null && $latestWeight >= 450) {
            $names = $sex === 'M' ? ['toro'] : ['vaca'];
            return $this->findVacunoEtapaByNames($names, $sex);
        }

        $names = $sex === 'M' ? ['novillo'] : ['novilla'];
        return $this->findVacunoEtapaByNames($names, $sex);
    }

    private function findVacunoEtapaByNames(array $names, string $sex): ?Etapa
    {
        $sexValues = $sex === 'F' ? ['F', 'H'] : ['M'];
        $normalizedNames = array_map('strtolower', $names);

        $candidates = Etapa::query()
            ->forTipoAnimal(3)
            ->whereIn('etapa_sexo', $sexValues)
            ->get();

        return $candidates->first(function (Etapa $etapa) use ($normalizedNames) {
            return in_array(strtolower((string) $etapa->etapa_nombre), $normalizedNames, true);
        });
    }

    private function buildWeightCheck(int $ageDays, ?float $latestWeight): array
    {
        $targetWeight = null;

        if ($ageDays <= 548) {
            $targetWeight = 113.0;
        } elseif ($ageDays <= 913) {
            $targetWeight = 225.0;
        } else {
            $targetWeight = 450.0;
        }

        if ($latestWeight === null) {
            return [
                'latest_weight' => null,
                'target_weight' => $targetWeight,
                'meets_target' => null,
                'status' => 'sin_registro',
            ];
        }

        return [
            'latest_weight' => $latestWeight,
            'target_weight' => $targetWeight,
            'meets_target' => $latestWeight >= $targetWeight,
            'status' => $latestWeight >= $targetWeight ? 'ok' : 'bajo_objetivo',
        ];
    }

    private function normalizeSex(?string $sex): string
    {
        $value = strtoupper((string) $sex);

        if ($value === 'H') {
            return 'F';
        }

        return $value === 'M' ? 'M' : 'F';
    }
}
