<?php

namespace App\Http\Controllers\Api; // Make sure the namespace is correct

use App\Exports\WorkersStandaloneExport; // Import your Excel export class
use App\Http\Controllers\Controller;
// Make sure to import your Mailable class (adjust namespace if needed)
use App\Mail\OperatorReportMail;
use App\Models\Operator; // *** USE OPERATOR MODEL ***
use Barryvdh\DomPDF\Facade\Pdf; // Import DomPDF facade
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log; // Important for logging
use Illuminate\Support\Facades\Mail; // Import Mail facade
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;          // Import Str facade for URL manipulation
use Maatwebsite\Excel\Facades\Excel;

class WorkerController extends Controller // Or the name you use
{
    // --- OTHER METHODS ---
    // ... (getProcessedOperatorData, generateExcelStandalone, generatePdfStandalone) ...
    // --- (Include the full code for getProcessedOperatorData, generateExcelStandalone, generatePdfStandalone as shown in previous responses) ---

     /**
     * Método privado para obtener, ordenar y procesar los datos del operador.
     * Reutilizado por los métodos de exportación de Excel y PDF.
     * Devuelve los datos con fechas SIN FORMATEAR (objetos Carbon o strings de DB).
     *
     * @param string $fromDate Fecha de inicio (YYYY-MM-DD)
     * @param string $toDate Fecha de fin (YYYY-MM-DD)
     * @param bool $filterPosts Si se deben filtrar operadores sin posts activos
     * @return array Datos procesados listos para la exportación o vista, o null en caso de error.
     */
    private function getProcessedOperatorData(string $fromDate, string $toDate, bool $filterPosts): ?array
    {
        Log::info('getProcessedOperatorData: Iniciando obtención y procesamiento de datos.');

        // 2. Obtención de Datos Crudos
        Log::info('getProcessedOperatorData: Iniciando consulta a la base de datos para Operators.');
        $operators = collect();
        try {
            $operatorsQuery = Operator::query()
                ->with([
                    'operatorPosts' => function ($query) use ($fromDate, $toDate) {
                        $query->with(['productList', 'rfidReading']);
                        $endDateForQuery = Carbon::parse($toDate)->endOfDay();
                        $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $endDateForQuery]);
                    },
                ]);
            $operators = $operatorsQuery->get();
            Log::info('getProcessedOperatorData: Consulta a DB completada.', ['operators_count' => $operators->count()]);
        } catch (\Exception $e) {
             Log::error('getProcessedOperatorData: EXCEPCIÓN durante la consulta a la base de datos.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
             ]);
             return null; // Indicar fallo
        }

        if ($operators->isEmpty()) {
            Log::warning('getProcessedOperatorData: No se encontraron operadores para el rango de fechas especificado.');
            return []; // Devolver array vacío si no hay operadores
        }

        // 3. Procesamiento de Datos: Ordenar
        Log::info('getProcessedOperatorData: Iniciando ordenación de operadores.');
        $sortedOperators = collect();
        try {
            $sortedOperators = $operators->sort(function ($operatorA, $operatorB) {
                $findFirstActivePost = function ($operator) {
                    if (empty($operator->operatorPosts)) { return null; }
                    return $operator->operatorPosts
                        ->filter(fn($post) => isset($post->count) && is_numeric($post->count) && $post->count > 0 && !empty($post->created_at))
                        ->sortBy('created_at')
                        ->first();
                };
                $firstPostA = $findFirstActivePost($operatorA);
                $firstPostB = $findFirstActivePost($operatorB);
                $puestoNameA = $firstPostA?->rfidReading?->name;
                $puestoNameB = $firstPostB?->rfidReading?->name;

                if ($puestoNameA && $puestoNameB) {
                    $nameComparison = strcmp($puestoNameA, $puestoNameB);
                    if ($nameComparison !== 0) return $nameComparison;
                    return strcmp($operatorA->name ?? '', $operatorB->name ?? '');
                } elseif ($puestoNameA) { return -1; }
                elseif ($puestoNameB) { return 1; }
                else { return strcmp($operatorA->name ?? '', $operatorB->name ?? ''); }
            });
            Log::info('getProcessedOperatorData: Ordenación completada.');
        } catch (\Exception $e) {
            Log::error('getProcessedOperatorData: EXCEPCIÓN durante la ordenación.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            return null; // Indicar fallo
        }

        // 4. Preparación Final de Datos (Formato Agrupado con Separador v2)
        Log::info('getProcessedOperatorData: Iniciando preparación de datos (formato agrupado con separador v2).');
        $processedData = [];
        $emptyRowStructure = [ // Estructura para datos y fila vacía
            'worker_client_id' => '', 'worker_name' => '', 'total_quantity_sum' => '',
            'post_name' => '', 'post_created_at' => '', 'post_finish_at' => '',
            'post_count' => '', 'product_name' => ''
        ];
        $isFirstOperatorProcessed = true;

        try {
            foreach ($sortedOperators as $index => $operator) {
                 $activePosts = collect();
                 if (!empty($operator->operatorPosts)) {
                      $activePosts = $operator->operatorPosts->filter(fn($post) => isset($post->count) && is_numeric($post->count) && $post->count > 0 && !empty($post->created_at));
                 }
                $showOperator = !$filterPosts || !$activePosts->isEmpty();
                if (!$showOperator) continue;

                if (!$isFirstOperatorProcessed) {
                    $processedData[] = $emptyRowStructure; // Añadir separador ANTES
                }

                $totalQuantitySum = $activePosts->sum('count');
                $sortedActivePostsForProcessing = $activePosts->sortBy('created_at');
                $isFirstRowForWorker = true;

                if ($sortedActivePostsForProcessing->isNotEmpty()) {
                    foreach ($sortedActivePostsForProcessing as $post) {
                         if ($isFirstRowForWorker) {
                             $processedData[] = [
                                 'worker_client_id' => $operator->client_id ?? '-',
                                 'worker_name' => $operator->name ?? 'Sin Nombre',
                                 'total_quantity_sum' => $totalQuantitySum,
                                 'post_name' => $post->rfidReading?->name ?? 'N/A',
                                 // --- NO FORMATEAR FECHAS AQUÍ ---
                                 'post_created_at' => $post->created_at, // Pasar objeto/string original
                                 'post_finish_at' => $post->finish_at,   // Pasar objeto/string original
                                 // ------------------------------
                                 'post_count' => $post->count ?? 0,
                                 'product_name' => $post->productList?->name ?? 'N/A',
                             ];
                             $isFirstRowForWorker = false;
                         } else {
                             $processedData[] = [
                                 'worker_client_id' => '', 'worker_name' => '', 'total_quantity_sum' => '',
                                 'post_name' => $post->rfidReading?->name ?? 'N/A',
                                 // --- NO FORMATEAR FECHAS AQUÍ ---
                                 'post_created_at' => $post->created_at, // Pasar objeto/string original
                                 'post_finish_at' => $post->finish_at,   // Pasar objeto/string original
                                 // ------------------------------
                                 'post_count' => $post->count ?? 0,
                                 'product_name' => $post->productList?->name ?? 'N/A',
                             ];
                         }
                    }
                } else { // Operador sin posts activos (y filtro desactivado)
                    $processedData[] = [
                        'worker_client_id' => $operator->client_id ?? '-',
                        'worker_name' => $operator->name ?? 'Sin Nombre',
                        'total_quantity_sum' => 0,
                        'post_name' => '',
                        // --- NO FORMATEAR FECHAS AQUÍ ---
                        'post_created_at' => '', // O null si se prefiere
                        'post_finish_at' => '',  // O null si se prefiere
                         // ------------------------------
                        'post_count' => '', 'product_name' => '',
                    ];
                }
                $isFirstOperatorProcessed = false;
            }
            Log::info('getProcessedOperatorData: Preparación de datos completada.', ['total_rows' => count($processedData)]);
            return $processedData;
        } catch (\Exception $e) {
             Log::error('getProcessedOperatorData: EXCEPCIÓN durante la preparación de datos.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
             ]);
             return null; // Indicar fallo
        }
    }


    /**
     * Genera y descarga un archivo Excel.
     * (Código anterior refactorizado para usar el método helper)
      * @param Request $request
      * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function generateExcelStandalone(Request $request)
    {
        Log::info('generateExcelStandalone: METHOD STARTED.');
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date_format:Y-m-d', 'to_date' => 'required|date_format:Y-m-d',
            'filter_posts' => 'sometimes|in:true,false',
        ]);
        if ($validator->fails()) {
            Log::error('generateExcelStandalone: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validatedData = $validator->validated();
        $fromDate = $validatedData['from_date'];
        $toDate = $validatedData['to_date'];
        $filterPosts = $request->query('filter_posts', 'true') === 'true';
        Log::info('generateExcelStandalone: Parameters validated.', ['from' => $fromDate, 'to' => $toDate, 'filter' => $filterPosts]);

        // Call helper method to get processed data (unformatted dates)
        $excelData = $this->getProcessedOperatorData($fromDate, $toDate, $filterPosts);

        // Handle error if helper failed
        if ($excelData === null) {
             return response()->json(['message' => 'Internal server error while processing data.'], 500);
        }

        // Generate Excel Filename
        $fileNameDatePart = $fromDate . '_a_' . $toDate;
        $filterSuffix = $filterPosts ? '_ConPuestosActivos' : '_Todos';
        $fileName = 'Informe_Operadores_' . $fileNameDatePart . $filterSuffix . '_Backend_Agrupado.xlsx';
        Log::info('generateExcelStandalone: Filename generated.', ['filename' => $fileName]);

        // Download Excel File
        Log::info('generateExcelStandalone: Attempting to generate and download Excel file.');
        try {
            if (!class_exists(WorkersStandaloneExport::class)) {
                 Log::critical('generateExcelStandalone: Export class WorkersStandaloneExport does not exist.');
                 return response()->json(['message' => 'Internal server error: Export class missing.'], 500);
            }
            // Export class will format the received dates
            return Excel::download(new WorkersStandaloneExport($excelData), $fileName);
        } catch (\Exception $e) {
            Log::error('generateExcelStandalone: FINAL EXCEPTION during Excel generation/download.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString() // <-- REVISA ESTE LOG DETALLADO
            ]);
            return response()->json(['message' => 'Error interno del servidor al generar el archivo Excel final.'], 500);
        }
    }

    /**
     * Generates and downloads a PDF file.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function generatePdfStandalone(Request $request)
    {
        Log::info('generatePdfStandalone: METHOD STARTED.');
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date_format:Y-m-d', 'to_date' => 'required|date_format:Y-m-d',
            'filter_posts' => 'sometimes|in:true,false',
        ]);
        if ($validator->fails()) {
            Log::error('generatePdfStandalone: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validatedData = $validator->validated();
        $fromDate = $validatedData['from_date'];
        $toDate = $validatedData['to_date'];
        $filterPosts = $request->query('filter_posts', 'true') === 'true';
        Log::info('generatePdfStandalone: Parameters validated.', ['from' => $fromDate, 'to' => $toDate, 'filter' => $filterPosts]);

        // Call helper method to get processed data (unformatted dates)
        $pdfData = $this->getProcessedOperatorData($fromDate, $toDate, $filterPosts);

        // Handle error if helper failed
        if ($pdfData === null) {
             return response()->json(['message' => 'Internal server error while processing data.'], 500);
        }

        // Generate PDF Filename
        $fileNameDatePart = $fromDate . '_a_' . $toDate;
        $filterSuffix = $filterPosts ? '_ConPuestosActivos' : '_Todos';
        $fileName = 'Informe_Operadores_' . $fileNameDatePart . $filterSuffix . '_Backend_Agrupado.pdf'; // <-- .pdf extension
        Log::info('generatePdfStandalone: Filename generated.', ['filename' => $fileName]);

        // Generate and Download PDF using DomPDF
        Log::info('generatePdfStandalone: Attempting to generate and download PDF file.');
        try {
            // Pass data to Blade view 'exports.workers_pdf'
            // Blade view will format the received dates
            $pdf = Pdf::loadView('exports.workers_pdf', [
                'data' => $pdfData,
                'fromDate' => $fromDate, // Pass dates to display in PDF title
                'toDate' => $toDate
            ]);

            // Optional: Set paper size and orientation
            // $pdf->setPaper('a4', 'landscape');

            // Download the PDF
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('generatePdfStandalone: EXCEPTION during PDF generation/download.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            // Return JSON on error
            return response()->json(['message' => 'Internal server error generating the PDF file.'], 500);
        }
    }

    /**
     * Sends operator reports (PDF & Excel links) via email.
     * Uses current date for the reports.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendReportsByEmail(Request $request)
    {
        Log::info('sendReportsByEmail: METHOD STARTED.');

        // 1. Validate Email Parameter
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'filter_posts' => 'sometimes|in:true,false', // Optional filter
        ]);

        if ($validator->fails()) {
            Log::error('sendReportsByEmail: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $recipientEmail = $validator->validated()['email'];
        // Use current date for the report links
        $reportDate = Carbon::today()->format('Y-m-d');
        $filterPosts = $request->query('filter_posts', 'true') === 'true'; // Default to true if not provided
        Log::info('sendReportsByEmail: Parameters validated.', ['email' => $recipientEmail, 'date' => $reportDate, 'filter' => $filterPosts]);

        // 2. Construct Download URLs and Web Interface URL
        try {
            // Build query parameters string for reports
            $queryParams = http_build_query([
                'from_date' => $reportDate,
                'to_date' => $reportDate,
                'filter_posts' => $filterPosts ? 'true' : 'false' // Ensure boolean is string 'true'/'false'
            ]);

            // Get base URL from config, remove trailing slash if present
            $baseUrl = rtrim(config('app.url'), '/');
            if (!$baseUrl) {
                 Log::error('sendReportsByEmail: APP_URL no está configurada en .env.');
                 throw new \Exception('APP_URL configuration is missing.');
            }

            // Construct full URLs for Excel and PDF using absolute paths
            $excelUrl = $baseUrl . '/api/workers-export/generate-excel?' . $queryParams;
            $pdfUrl = $baseUrl . '/api/workers-export/generate-pdf?' . $queryParams;

            // Construct URL for the web interface
            $webInterfaceUrl = $baseUrl . '/workers/export.html'; // <-- NUEVA URL

            Log::info('sendReportsByEmail: Download and Web URLs constructed.', [
                'excel' => $excelUrl,
                'pdf' => $pdfUrl,
                'web' => $webInterfaceUrl // <-- Log de la nueva URL
            ]);

        } catch (\Exception $e) {
             Log::error('sendReportsByEmail: EXCEPTION during URL construction.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
             ]);
             return response()->json(['message' => 'Internal server error constructing report URLs.'], 500);
        }


        // 3. Dispatch Email (Using Queue or Send based on previous debugging)
        // --- IMPORTANTE: Decide si usar send() o queue() ---
        $useQueue = true; // <-- Cambia a false si necesitas envío síncrono temporalmente
        // ----------------------------------------------------

        Log::info('sendReportsByEmail: Attempting to ' . ($useQueue ? 'queue' : 'send synchronously') . ' email.', ['recipient' => $recipientEmail]);
        try {
            // Ensure the Mailable class exists
             if (!class_exists(OperatorReportMail::class)) {
                 Log::critical('sendReportsByEmail: Mailable class OperatorReportMail does not exist.');
                 return response()->json(['message' => 'Internal server error: Mailable class missing.'], 500);
            }

            // Create the Mailable instance, passing all URLs
            $mailable = new OperatorReportMail($excelUrl, $pdfUrl, $webInterfaceUrl, $reportDate); // <-- Pasar la nueva URL

            // Send or Queue the email
            if ($useQueue) {
                 Mail::to($recipientEmail)->queue($mailable);
                 Log::info('sendReportsByEmail: Email successfully queued.', ['recipient' => $recipientEmail]);
                 $successMessage = 'El correo con los informes para ' . $reportDate . ' ha sido programado para envío a ' . $recipientEmail;
            } else {
                 Mail::to($recipientEmail)->send($mailable);
                 Log::info('sendReportsByEmail: Email sent successfully (synchronously).', ['recipient' => $recipientEmail]);
                 $successMessage = 'El correo con los informes para ' . $reportDate . ' ha sido enviado a ' . $recipientEmail;
            }

            // 4. Return Success Response
            return response()->json([
                'success' => true,
                'message' => $successMessage
            ]);

        } catch (\Exception $e) {
            // Log error if sending/queuing fails
            Log::error('sendReportsByEmail: EXCEPTION while ' . ($useQueue ? 'queueing' : 'sending synchronously') . ' email.', [
                'recipient' => $recipientEmail,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error interno del servidor al intentar enviar el correo.'], 500);
        }
    }


    // --- Your original method returning JSON ---
     public function completeList(Request $request)
     {
         // ... (code unchanged) ...
         $fromDate = $request->get('from_date');
         $toDate   = $request->get('to_date');
         $endDateForQuery = $toDate ? Carbon::parse($toDate)->endOfDay() : null;
         $operators = Operator::with(['operatorPosts' => function ($query) use ($fromDate, $endDateForQuery) {
             $query->with(['productList', 'rfidReading']);
             if ($fromDate && $endDateForQuery) {
                 $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $endDateForQuery]);
             } elseif ($fromDate) {
                 $query->where('created_at', '>=', $fromDate . ' 00:00:00');
             } elseif ($endDateForQuery) {
                 $query->where('created_at', '<=', $endDateForQuery);
             }
         }])->get();
         return response()->json([
             'success' => true,
             'data'    => $operators
         ]);
     }

}