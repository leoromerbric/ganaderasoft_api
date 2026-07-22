<?php

namespace App\Http\Middleware\Legacy\Sanidad;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeIndexVacunacion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->header('X-API-VERSION') === '2') {
            return $response;
        }

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    // Paginado
                    $data['data']['data'] = array_map(function ($item) {
                        return $this->mapToLegacy($item);
                    }, $data['data']['data']);
                } elseif (is_array($data['data'])) {
                    // No paginado
                    $data['data'] = array_map(function ($item) {
                        return $this->mapToLegacy($item);
                    }, $data['data']);
                }
                
                $response->setContent(json_encode($data));
            }
        }

        return $response;
    }

    private function mapToLegacy(array $item): array
    {
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
            
            // Relaciones si están presentes
            'vacuna'                    => $item['vacuna'] ?? null,
            'rebano'                    => $item['rebano'] ?? null,
            'animales'                  => $item['animales'] ?? null,
            'animales_count'            => $item['animales_count'] ?? null,
        ];

        return array_filter($legacy, function($value) {
            return $value !== null;
        });
    }
}
