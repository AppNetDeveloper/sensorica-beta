<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiService
{
    public function callExternalApi($apiQueue, $config, $newBoxNumber, $maxKg, $dimensionFinal, $uniqueBarcoder)
    {
        Log::info("Llamada a la API externa para el Modbus ID: {$config->id}");

        $dataToSend = [
            'token' => $apiQueue->token_back,
            'rec_box' => $newBoxNumber,
            'max_kg' => $maxKg,
            'last_dimension' => $dimensionFinal,
            'last_barcoder' => $uniqueBarcoder
        ];

        try {
            $response = Http::post($apiQueue->url_back, $dataToSend);

            if ($response->successful()) {
                Log::info("Respuesta exitosa de la API externa para el Modbus ID: {$config->id}", [
                    'response' => $response->json(),
                ]);
            } else {
                Log::error("Error en la respuesta de la API externa para el Modbus ID: {$config->id}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error al llamar a la API externa para el Modbus ID: {$config->id}", [
                'error' => $e->getMessage(),
            ]);
        }

        $apiQueue->used = true;
        $apiQueue->save();
        $apiQueue->delete();
    }
}
