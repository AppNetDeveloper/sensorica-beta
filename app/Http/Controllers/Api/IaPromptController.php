<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IaPrompt; // Asegúrate de que la ruta a tu modelo es correcta
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IaPromptController extends Controller
{
    /**
     * Display the specified prompt by its key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByKey(string $key): JsonResponse
    {
        // Busca el prompt por su clave y que esté activo
        $prompt = IaPrompt::where('key', $key)
                          ->where('is_active', true)
                          ->first();

        if (!$prompt) {
            return response()->json(['message' => 'Prompt no encontrado o inactivo.'], 404);
        }

        // Preparar ai_url y ai_token desde .env
        $aiUrl = rtrim(env('AI_URL', ''), '/');
        $rawToken = env('AI_TOKEN', '');
        $aiToken = $rawToken;
        if (!empty($rawToken) && stripos($rawToken, 'bearer ') !== 0) {
            $aiToken = 'Bearer ' . $rawToken;
        }

        // Devuelve los campos que el frontend necesita, incluyendo ai_url y ai_token desde .env
        return response()->json([
            'key' => $prompt->key,
            'name' => $prompt->name,
            'content' => $prompt->content,
            'model_name' => $prompt->model_name,
            'ai_url' => $aiUrl,
            'ai_token' => $aiToken,
        ]);
    }

    /**
     * Display a listing of the active prompts.
     * (Opcional, si necesitas listar todos los prompts activos)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $prompts = IaPrompt::where('is_active', true)
                           ->get(['key', 'name', 'content', 'model_name']);

        return response()->json($prompts);
    }
}