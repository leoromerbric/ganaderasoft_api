<?php

namespace App\Http\Middleware\Legacy\PersonalFinca;

use Closure;
use Illuminate\Http\Request;

class NormalizeIndex
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->isSuccessful() && $request->header('X-API-VERSION') !== '2') {
            $data = $response->getData(true);
            
            if (isset($data['data'])) {
                $mappedData = array_map(function ($item) {
                    return [
                        'id_Tecnico' => $item['id'],
                        'id_Finca' => $item['finca_id'],
                        'Cedula' => isset($item['persona']['cedula']) ? (int) preg_replace('/[^0-9]/', '', $item['persona']['cedula']) : null,
                        'Nombre' => $item['persona']['nombre'] ?? null,
                        'Apellido' => $item['persona']['apellido'] ?? null,
                        'Telefono' => $item['persona']['telefono'] ?? null,
                        'Correo' => $item['persona']['correo'] ?? null,
                        'Tipo_Trabajador' => $item['tipo_trabajador']['nombre'] ?? null,
                        'created_at' => $item['created_at'] ?? null,
                        'updated_at' => $item['updated_at'] ?? null,
                    ];
                }, $data['data']);

                $data['data'] = $mappedData;
                
                if (isset($data['meta'])) {
                    $data['pagination'] = [
                        'current_page' => $data['meta']['current_page'],
                        'last_page' => $data['meta']['last_page'],
                        'per_page' => $data['meta']['per_page'],
                        'total' => $data['meta']['total'],
                    ];
                    unset($data['meta']);
                    unset($data['links']);
                }

                $response->setData($data);
            }
        }

        return $response;
    }
}
