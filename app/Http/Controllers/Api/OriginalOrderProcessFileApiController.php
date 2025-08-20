<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderProcessFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OriginalOrderProcessFileApiController extends Controller
{
    /**
     * @OA\SecurityScheme(
     *     securityScheme="CustomerToken",
     *     type="apiKey",
     *     in="header",
     *     name="X-Customer-Token",
     *     description="Token del cliente para autenticación de subida/borrado de archivos. Alternativamente se puede enviar por body o query como 'token'."
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/process-files/upload",
     *     summary="Subir archivo de proceso por token de cliente",
     *     description="Permite subir un archivo (imagen o PDF) asociado a un OriginalOrderProcess validando el token del cliente propietario del pedido.",
     *     tags={"ProcessFiles"},
     *     security={{"CustomerToken":{}}},
     *     @OA\Parameter(
     *         name="X-Customer-Token",
     *         in="header",
     *         description="Token del cliente (alternativa al campo 'token' en el body)",
     *         required=false,
     *         @OA\Schema(type="string", example="cust_abcdef123456")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             "multipart/form-data": {
     *                 "schema": {
     *                     "type": "object",
     *                     "required": {"token", "original_order_process_id", "file"},
     *                     "properties": {
     *                         "token": {"type": "string", "description": "Token único del cliente", "example": "cust_abcdef123456"},
     *                         "original_order_process_id": {"type": "integer", "description": "ID del proceso original al que se asocia el archivo", "example": 12345},
     *                         "file": {"type": "string", "format": "binary", "description": "Archivo a subir (jpg, jpeg, png, gif, webp, pdf) máx 10MB"}
     *                     }
     *                 }
     *             }
     *         }
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Archivo subido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="file", type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="token", type="string", example="f5f7a0fb-6b5b-4a7a-9ec6-8c8b1f9a2f0d"),
     *                 @OA\Property(property="original_name", type="string", example="Plano_123.pdf"),
     *                 @OA\Property(property="mime_type", type="string", example="application/pdf"),
     *                 @OA\Property(property="size", type="integer", example=284233),
     *                 @OA\Property(property="extension", type="string", example="pdf"),
     *                 @OA\Property(property="disk", type="string", example="public"),
     *                 @OA\Property(property="path", type="string", example="oop-files/f5f7a0fb-6b5b-4a7a-9ec6-8c8b1f9a2f0d.pdf"),
     *                 @OA\Property(property="public_url", type="string", example="/storage/oop-files/f5f7a0fb-6b5b-4a7a-9ec6-8c8b1f9a2f0d.pdf"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Invalid token"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="El proceso no pertenece al cliente",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Process does not belong to the provided customer"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proceso no encontrado",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Process not found"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación / Límite de archivos alcanzado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Fallo en la subida",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Upload failed"))
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => ['nullable', 'string'],
                'original_order_process_id' => ['required', 'integer', 'min:1'],
                'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'], // 10MB
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $ve->errors(),
            ], 422);
        }

        // Token por header o body
        $token = $request->header('X-Customer-Token') ?: ($validated['token'] ?? null);
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }
        $processId = (int) $validated['original_order_process_id'];

        // Find customer by token
        $customer = Customer::where('token', $token)->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        // Find process and ensure ownership by customer's original order
        $process = OriginalOrderProcess::query()->find($processId);
        if (!$process) {
            return response()->json([
                'success' => false,
                'message' => 'Process not found',
            ], 404);
        }

        $originalOrder = OriginalOrder::find($process->original_order_id);
        if (!$originalOrder || $originalOrder->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Process does not belong to the provided customer',
            ], 403);
        }

        try {
            $file = $validated['file'];
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getClientMimeType();
            $size = $file->getSize();
            $originalName = $file->getClientOriginalName();

            // Enforce max files per process
            $currentCount = $process->files()->count();
            $maxFiles = 8;
            if ($currentCount >= $maxFiles) {
                return response()->json([
                    'success' => false,
                    'message' => __('Maximum files reached for this process (:max)', ['max' => $maxFiles]),
                ], 422);
            }

            // Ensure directory exists on public disk
            if (!Storage::disk('public')->exists('oop-files')) {
                Storage::disk('public')->makeDirectory('oop-files');
            }

            // Save with uuid token as filename
            $tokenFile = Str::uuid()->toString();
            $filename = $tokenFile . '.' . $ext;
            $relativePath = 'oop-files/' . $filename;

            Storage::disk('public')->putFileAs('oop-files', $file, $filename);

            $model = OriginalOrderProcessFile::create([
                'original_order_process_id' => $process->id,
                'token' => $tokenFile,
                'original_name' => $originalName,
                'mime_type' => $mime,
                'size' => $size,
                'extension' => $ext,
                'disk' => 'public',
                'path' => $relativePath,
            ]);

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $model->id,
                    'token' => $model->token,
                    'original_name' => $model->original_name,
                    'mime_type' => $model->mime_type,
                    'size' => $model->size,
                    'extension' => $model->extension,
                    'disk' => $model->disk,
                    'path' => $model->path,
                    'public_url' => $model->public_url,
                    'created_at' => $model->created_at?->toDateTimeString(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('API process file upload failed', [
                'process_id' => $processId,
                'customer_id' => $customer->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/process-files/{id}",
     *     summary="Eliminar un archivo de proceso por ID",
     *     description="Elimina un archivo asociado a un proceso verificando que el token del cliente propietario del pedido sea válido. Se acepta el token por header (X-Customer-Token) o por query/body como 'token'.",
     *     tags={"ProcessFiles"},
     *     security={{"CustomerToken":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del archivo de proceso",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="X-Customer-Token",
     *         in="header",
     *         required=false,
     *         description="Token del cliente (alternativa al parámetro 'token')",
     *         @OA\Schema(type="string", example="cust_abcdef123456")
     *     ),
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=false,
     *         description="Token del cliente (alternativa al header)",
     *         @OA\Schema(type="string", example="cust_abcdef123456")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Archivo eliminado correctamente",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="File deleted"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="Invalid token"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="El archivo no pertenece al cliente",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="File does not belong to the provided customer"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Archivo no encontrado",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=false), @OA\Property(property="message", type="string", example="File not found"))
     *     )
     * )
     */
    public function destroy(Request $request, int $id)
    {
        // Token por header o query/body
        $token = $request->header('X-Customer-Token') ?: $request->input('token');
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        $customer = Customer::where('token', $token)->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        $file = OriginalOrderProcessFile::find($id);
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        // Verificar pertenencia del archivo al cliente
        $process = OriginalOrderProcess::find($file->original_order_process_id);
        if (!$process) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }
        $originalOrder = OriginalOrder::find($process->original_order_id);
        if (!$originalOrder || (int)$originalOrder->customer_id !== (int)$customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'File does not belong to the provided customer',
            ], 403);
        }

        // Borrar de storage si existe
        if ($file->disk && $file->path && Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted',
        ]);
    }
}
