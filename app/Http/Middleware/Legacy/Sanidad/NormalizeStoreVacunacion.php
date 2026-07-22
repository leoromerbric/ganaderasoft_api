<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeStoreVacunacion
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-API-VERSION') !== '2') {
            $input = $request->all();
            $payload = [];

            if (array_key_exists('vacunacion_vacuna_id', $input)) $payload['vacuna_id'] = $input['vacunacion_vacuna_id'];
            elseif (array_key_exists('vacuna_id', $input)) $payload['vacuna_id'] = $input['vacuna_id'];

            if (array_key_exists('vacunacion_casa_id', $input)) $payload['casa_comercial_id'] = $input['vacunacion_casa_id'];
            elseif (array_key_exists('casa_comercial_id', $input)) $payload['casa_comercial_id'] = $input['casa_comercial_id'];

            if (array_key_exists('vacunacion_rebano_id', $input)) $payload['rebano_id'] = $input['vacunacion_rebano_id'];
            elseif (array_key_exists('rebano_id', $input)) $payload['rebano_id'] = $input['rebano_id'];

            if (array_key_exists('vacunacion_modo_seleccion', $input)) $payload['modo_seleccion'] = $input['vacunacion_modo_seleccion'];
            elseif (array_key_exists('modo_seleccion', $input)) $payload['modo_seleccion'] = $input['modo_seleccion'];

            if (array_key_exists('vacunacion_filtros', $input)) $payload['filtros'] = $input['vacunacion_filtros'];
            elseif (array_key_exists('filtros', $input)) $payload['filtros'] = $input['filtros'];

            if (array_key_exists('vacunacion_fecha', $input)) $payload['fecha'] = $input['vacunacion_fecha'];
            elseif (array_key_exists('fecha', $input)) $payload['fecha'] = $input['fecha'];

            if (array_key_exists('vacunacion_costo_dosis', $input)) $payload['costo_dosis'] = $input['vacunacion_costo_dosis'];
            elseif (array_key_exists('costo_dosis', $input)) $payload['costo_dosis'] = $input['costo_dosis'];
            
            if (array_key_exists('vacunacion_observacion', $input)) $payload['observacion'] = $input['vacunacion_observacion'];
            elseif (array_key_exists('observacion', $input)) $payload['observacion'] = $input['observacion'];

            if (array_key_exists('vacunacion_animal_ids', $input)) $payload['animal_ids'] = $input['vacunacion_animal_ids'];
            elseif (array_key_exists('animal_ids', $input)) $payload['animal_ids'] = $input['animal_ids'];

            $request->replace($payload);
        }

        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        if ($response->getStatusCode() === 422) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['errors'])) {
                $errorMapping = [
                    'vacuna_id'         => 'vacunacion_vacuna_id',
                    'casa_comercial_id' => 'vacunacion_casa_id',
                    'rebano_id'         => 'vacunacion_rebano_id',
                    'modo_seleccion'    => 'vacunacion_modo_seleccion',
                    'filtros'           => 'vacunacion_filtros',
                    'fecha'             => 'vacunacion_fecha',
                    'costo_dosis'       => 'vacunacion_costo_dosis',
                    'observacion'       => 'vacunacion_observacion',
                    'animal_ids'        => 'vacunacion_animal_ids',
                ];

                $legacyErrors = [];
                foreach ($content['errors'] as $key => $messages) {
                    // Manage dot notation specifically for array validation errors like animal_ids.* or filtros.sexo
                    $baseKey = explode('.', $key)[0];
                    if (isset($errorMapping[$baseKey])) {
                        $newKey = str_replace($baseKey, $errorMapping[$baseKey], $key);
                    } else {
                        $newKey = $errorMapping[$key] ?? $key;
                    }
                    $legacyErrors[$newKey] = $messages;
                }
                $content['errors'] = $legacyErrors;
                $response->setContent(json_encode($content));
            }
            return $response;
        }

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);
            if (isset($data['data']) && is_array($data['data'])) {
                $item = $data['data'];
                $legacy = [
                    'vacunacion_id'             => $item['id'] ?? null,
                    'vacunacion_vacuna_id'      => $item['vacuna_id'] ?? null,
                    'vacunacion_casa_id'        => $item['casa_comercial_id'] ?? null,
                    'vacunacion_rebano_id'      => $item['rebano_id'] ?? null,
                    'vacunacion_modo_seleccion' => $item['modo_seleccion'] ?? null,
                    'vacunacion_filtros'        => $item['filtros'] ?? null,
                    'vacunacion_fecha'          => $item['fecha'] ?? null,
                    'vacunacion_costo_dosis'    => $item['costo_dosis'] ?? null,
                    'vacunacion_total_animales' => $item['total_animales'] ?? null,
                    'vacunacion_monto_total'    => $item['monto_total'] ?? null,
                    'vacunacion_observacion'    => $item['observacion'] ?? null,
                    
                    'vacuna'                    => $item['vacuna'] ?? null,
                    'rebano'                    => $item['rebano'] ?? null,
                    'animales'                  => $item['animales'] ?? null,
                    'animales_count'            => $item['animales_count'] ?? null,
                ];
                $data['data'] = array_filter($legacy, function($value) { return $value !== null; });
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }
}
