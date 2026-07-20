<?php

namespace App\Http\Middleware\Legacy\InventarioGeneral;

use Closure;
use Illuminate\Http\Request;

class NormalizeIndex
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-VERSION') === '2') {
            return $next($request);
        }

        $response = $next($request);

        if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            if (isset($data['data'])) {
                $mappedData = array_map(function ($item) {
                    return [
                        'id_InvGen' => $item['id'],
                        'id_Finca' => $item['finca_id'],
                        'Num_Personal' => $item['num_personal'],
                        'Fecha_Inventario' => $item['fecha_inventario'],
                    ];
                }, $data['data']);

                $legacyResponse = [
                    'current_page' => $data['meta']['current_page'] ?? 1,
                    'data' => $mappedData,
                    'first_page_url' => $data['links']['first'] ?? null,
                    'from' => $data['meta']['from'] ?? null,
                    'last_page' => $data['meta']['last_page'] ?? 1,
                    'last_page_url' => $data['links']['last'] ?? null,
                    'links' => $data['meta']['links'] ?? [],
                    'next_page_url' => $data['links']['next'] ?? null,
                    'path' => $data['meta']['path'] ?? null,
                    'per_page' => $data['meta']['per_page'] ?? 15,
                    'prev_page_url' => $data['links']['prev'] ?? null,
                    'to' => $data['meta']['to'] ?? null,
                    'total' => $data['meta']['total'] ?? 0,
                ];

                $response->setContent(json_encode($legacyResponse));
            }
        }

        return $response;
    }
}
