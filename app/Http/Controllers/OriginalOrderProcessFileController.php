<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderProcessFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OriginalOrderProcessFileController extends Controller
{
    public function index(Customer $customer, OriginalOrder $originalOrder, OriginalOrderProcess $originalOrderProcess)
    {
        $this->authorize('original-order-list');
        $this->ensureHierarchy($customer, $originalOrder, $originalOrderProcess);

        $files = $originalOrderProcess->files()->latest()->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'token' => $f->token,
                'original_name' => $f->original_name,
                'mime_type' => $f->mime_type,
                'size' => $f->size,
                'extension' => $f->extension,
                'disk' => $f->disk,
                'path' => $f->path,
                'public_url' => $f->public_url,
                'created_at' => $f->created_at?->toDateTimeString(),
            ];
        });

        return response()->json(['data' => $files]);
    }

    public function store(Request $request, Customer $customer, OriginalOrder $originalOrder, OriginalOrderProcess $originalOrderProcess)
    {
        $this->authorize('original-order-edit');
        $this->ensureHierarchy($customer, $originalOrder, $originalOrderProcess);

        try {
            $validated = $request->validate([
                'file' => [
                    'required',
                    'file',
                    // Restrict to images and pdf
                    'mimes:jpg,jpeg,png,gif,webp,pdf',
                    'max:10240', // 10MB
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $ve->errors(),
            ], 422);
        }

        try {
            $file = $validated['file'];
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getClientMimeType();
            $size = $file->getSize();
            $originalName = $file->getClientOriginalName();

            // Enforce max files per process
            $currentCount = $originalOrderProcess->files()->count();
            $maxFiles = 8;
            if ($currentCount >= $maxFiles) {
                return response()->json([
                    'success' => false,
                    'message' => __('Maximum files reached for this process (:max)', ['max' => $maxFiles]),
                ], 422);
            }

            // Use UUID for broad Laravel compatibility
            $token = Str::uuid()->toString();
            $filename = $token . '.' . $ext;
            $relativePath = 'oop-files/' . $filename;

            // Ensure directory exists on public disk
            if (!Storage::disk('public')->exists('oop-files')) {
                Storage::disk('public')->makeDirectory('oop-files');
            }

            // Store publicly
            Storage::disk('public')->putFileAs('oop-files', $file, $filename);

            $model = OriginalOrderProcessFile::create([
                'original_order_process_id' => $originalOrderProcess->id,
                'token' => $token,
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
                ],
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('OriginalOrderProcess file upload failed', [
                'process_id' => $originalOrderProcess->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
            ], 500);
        }
    }

    public function destroy(Customer $customer, OriginalOrder $originalOrder, OriginalOrderProcess $originalOrderProcess, OriginalOrderProcessFile $file)
    {
        $this->authorize('original-order-edit');
        $this->ensureHierarchy($customer, $originalOrder, $originalOrderProcess);

        if ($file->original_order_process_id !== $originalOrderProcess->id) {
            return response()->json(['success' => false, 'message' => 'Archivo no pertenece al proceso.'], 404);
        }

        // Delete physical file if exists
        if ($file->disk === 'public' && $file->path && Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        $file->delete();

        return response()->json(['success' => true]);
    }

    private function ensureHierarchy(Customer $customer, OriginalOrder $originalOrder, OriginalOrderProcess $originalOrderProcess): void
    {
        if ($originalOrder->customer_id !== $customer->id || $originalOrderProcess->original_order_id !== $originalOrder->id) {
            abort(404);
        }
    }
}
