<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConfiguracionController extends Controller
{
    /**
     * Get Tipo Explotacion list.
     */
    public function tipoExplotacion()
    {
        try {
            $data = $this->getJsonData('tipo-explotacion.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de tipos de explotación obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de tipos de explotación',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Metodo Riego list.
     */
    public function metodoRiego()
    {
        try {
            $data = $this->getJsonData('metodo-riego.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de métodos de riego obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de métodos de riego',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get PH Suelo list.
     */
    public function phSuelo()
    {
        try {
            $data = $this->getJsonData('ph-suelo.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de pH de suelo obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de pH de suelo',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Textura Suelo list.
     */
    public function texturaSuelo()
    {
        try {
            $data = $this->getJsonData('textura-suelo.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de texturas de suelo obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de texturas de suelo',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Fuente Agua list.
     */
    public function fuenteAgua()
    {
        try {
            $data = $this->getJsonData('fuente-agua.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de fuentes de agua obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de fuentes de agua',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Sexo list.
     */
    public function sexo()
    {
        try {
            $data = $this->getJsonData('sexo.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de sexos obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de sexos',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Tipo Relieve list.
     */
    public function tipoRelieve()
    {
        try {
            $data = $this->getJsonData('tipo-relieve.json');
            return response()->json([
                'success' => true,
                'message' => 'Lista de tipos de relieve obtenida exitosamente',
                'data' => $data
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de tipos de relieve',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper method to read JSON data from resources.
     */
    private function getJsonData($filename)
    {
        $path = resource_path("datos-constantes/{$filename}");
        
        if (!file_exists($path)) {
            throw new \Exception("Archivo de configuración no encontrado: {$filename}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar JSON: " . json_last_error_msg());
        }

        return $data;
    }
}