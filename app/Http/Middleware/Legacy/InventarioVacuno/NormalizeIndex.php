<?php

namespace App\Http\Middleware\Legacy\InventarioVacuno;

use Closure;
use Illuminate\Http\Request;

class NormalizeIndex
{
    public function handle(Request $request, Closure $next)
    {
        // Add id_finca to request if it exists in input (for the service)
        if ($request->has('id_finca')) {
            $request->merge(['finca_id' => $request->input('id_finca')]);
        }

        $response = $next($request);

        if ($request->header('X-API-VERSION') == '2' || !$response->isSuccessful()) {
            return $response;
        }

        $data = $response->getData(true);

        if (isset($data['data'])) {
            $mappedData = array_map(function ($item) {
                return [
                    'id_InvVacuno' => $item['id'],
                    'id_Finca' => $item['finca_id'],
                    'Num_Becerra' => $item['num_becerra'],
                    'Num_Mauta' => $item['num_mauta'],
                    'Num_Novilla' => $item['num_novilla'],
                    'Num_Vaca' => $item['num_vaca'],
                    'Num_Becerro' => $item['num_becerro'],
                    'Num_Maute' => $item['num_maute'],
                    'Num_Torete' => $item['num_torete'],
                    'Num_Toro' => $item['num_toro'],
                    'Fecha_Inventario' => $item['fecha_inventario']
                ];
            }, $data['data']['data'] ?? $data['data']);
            
            // Re-assign paginated array properly
            if (isset($data['data']['data'])) {
                $data['data']['data'] = $mappedData;
            } else {
                $data['data'] = $mappedData;
            }

            if (isset($data['meta'])) {
                $data['meta'] = [
                    'current_page' => $data['meta']['current_page'] ?? 1,
                    'last_page' => $data['meta']['last_page'] ?? 1,
                    'per_page' => $data['meta']['per_page'] ?? 15,
                    'total' => $data['meta']['total'] ?? 0,
                ];
            }
            $response->setData($data);
        }

        return $response;
    }
}
