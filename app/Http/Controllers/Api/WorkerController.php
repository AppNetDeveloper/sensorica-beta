<?php

namespace App\Http\Controllers\Api;

use App\Exports\WorkersStandaloneExport;
use App\Http\Controllers\Controller;
use App\Mail\OperatorReportMail;
use App\Models\Operator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\AssignmentListMail;

class WorkerController extends Controller
{

    private function calculateCajasPorHora($count, $startTime, $endTime): string
    {
        $quantity = (float)($count ?? 0);

        if (!$startTime) {
            Log::warning('calculateCajasPorHora: startTime es nulo o vacío.');
            return 'N/A';
        }

        $carbonStartTime = null;
        try {
            if ($startTime instanceof Carbon) {
                $carbonStartTime = $startTime;
            } elseif (is_string($startTime)) {
                $carbonStartTime = Carbon::parse($startTime); // Carbon intentará adivinar el formato
            } else {
                Log::warning('calculateCajasPorHora: startTime no es Carbon ni string.', ['startTime_type' => gettype($startTime)]);
                return 'N/A';
            }
        } catch (\Exception $e) {
            Log::error('calculateCajasPorHora: Excepción al parsear startTime.', [
                'startTime_input' => $startTime,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'N/A';
        }

        $carbonEndTime = null;
        if ($endTime) {
            try {
                if ($endTime instanceof Carbon) {
                    $carbonEndTime = $endTime;
                } elseif (is_string($endTime)) {
                    $carbonEndTime = Carbon::parse($endTime);
                } else {
                    // Si no es Carbon ni string, pero existe, podríamos usar now() o retornar N/A
                    Log::warning('calculateCajasPorHora: endTime no es Carbon ni string, usando Carbon::now().', ['endTime_type' => gettype($endTime)]);
                    $carbonEndTime = Carbon::now(config('app.timezone'));
                }
            } catch (\Exception $e) {
                Log::warning('calculateCajasPorHora: Excepción al parsear endTime, usando Carbon::now().', [
                    'endTime_input' => $endTime,
                    'error' => $e->getMessage()
                ]);
                $carbonEndTime = Carbon::now(config('app.timezone'));
            }
        } else {
            $carbonEndTime = Carbon::now(config('app.timezone'));
        }

        // Log detallado antes de la comparación crucial
        Log::debug('calculateCajasPorHora: DEBUG INFO', [
            'input_startTime' => is_object($startTime) ? (method_exists($startTime, 'toDateTimeString') ? $startTime->toDateTimeString() : 'Carbon Object') : $startTime,
            'input_endTime' => is_object($endTime) ? (method_exists($endTime, 'toDateTimeString') ? $endTime->toDateTimeString() : 'Carbon Object') : $endTime,
            'parsed_carbonStartTime_val' => $carbonStartTime->toIso8601String(),
            'parsed_carbonStartTime_tz' => $carbonStartTime->tzName,
            'parsed_carbonEndTime_val' => $carbonEndTime->toIso8601String(),
            'parsed_carbonEndTime_tz' => $carbonEndTime->tzName,
            'app_timezone' => config('app.timezone'),
            'is_endTime_less_or_equal_to_startTime' => $carbonEndTime->lessThanOrEqualTo($carbonStartTime),
            'quantity' => $quantity
        ]);

        if ($carbonEndTime->lessThanOrEqualTo($carbonStartTime)) {
            Log::warning('calculateCajasPorHora: carbonEndTime es menor o igual que carbonStartTime.', [
                'carbonStartTime' => $carbonStartTime->toIso8601String(),
                'carbonEndTime' => $carbonEndTime->toIso8601String(),
            ]);
            return $quantity > 0 ? 'N/A' : '0.00';
        }

        $diffInSeconds = $carbonEndTime->diffInSeconds($carbonStartTime);
        if ($diffInSeconds <= 0) { // Doble chequeo
             Log::warning('calculateCajasPorHora: diffInSeconds es cero o negativo.', ['diffInSeconds' => $diffInSeconds]);
             return $quantity > 0 ? 'N/A' : '0.00';
        }

        $diffInHours = $diffInSeconds / 3600;

        if ($quantity === 0.0) {
            return '0.00';
        }

        $rate = $quantity / $diffInHours;
        return number_format($rate, 2, '.', '');
    }

    private function getProcessedOperatorData(string $fromDate, string $toDate, bool $filterPosts): ?array
    {
        Log::info('getProcessedOperatorData: Iniciando obtención y procesamiento de datos.');
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
             return null;
        }

        if ($operators->isEmpty()) {
            Log::warning('getProcessedOperatorData: No se encontraron operadores para el rango de fechas especificado.');
            return [];
        }

        Log::info('getProcessedOperatorData: Iniciando ordenación de operadores.');
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

        Log::info('getProcessedOperatorData: Iniciando preparación de datos.');
        $processedData = [];
        $emptyRowStructure = [
            'worker_client_id' => '', 'worker_name' => '', 'total_quantity_sum' => '',
            'post_name' => '', 'post_created_at' => '', 'post_finish_at' => '',
            'post_count' => '', 'post_cajas_hora' => '', 'product_name' => ''
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
                    $processedData[] = $emptyRowStructure;
                }

                $totalQuantitySum = $activePosts->sum('count');
                $sortedActivePostsForProcessing = $activePosts->sortBy('created_at');
                $isFirstRowForWorker = true;

                if ($sortedActivePostsForProcessing->isNotEmpty()) {
                    foreach ($sortedActivePostsForProcessing as $post) {
                         $cajasPorHora = $this->calculateCajasPorHora($post->count, $post->created_at, $post->finish_at);
                         if ($isFirstRowForWorker) {
                             $processedData[] = [
                                 'worker_client_id' => $operator->client_id ?? '-',
                                 'worker_name' => $operator->name ?? 'Sin Nombre',
                                 'total_quantity_sum' => $totalQuantitySum,
                                 'post_name' => $post->rfidReading?->name ?? 'N/A',
                                 'post_created_at' => $post->created_at,
                                 'post_finish_at' => $post->finish_at,
                                 'post_count' => $post->count ?? 0,
                                 'post_cajas_hora' => $cajasPorHora,
                                 'product_name' => $post->productList?->name ?? 'N/A',
                             ];
                             $isFirstRowForWorker = false;
                         } else {
                             $processedData[] = [
                                 'worker_client_id' => '', 'worker_name' => '', 'total_quantity_sum' => '',
                                 'post_name' => $post->rfidReading?->name ?? 'N/A',
                                 'post_created_at' => $post->created_at,
                                 'post_finish_at' => $post->finish_at,
                                 'post_count' => $post->count ?? 0,
                                 'post_cajas_hora' => $cajasPorHora,
                                 'product_name' => $post->productList?->name ?? 'N/A',
                             ];
                         }
                    }
                } else {
                    $processedData[] = [
                        'worker_client_id' => $operator->client_id ?? '-',
                        'worker_name' => $operator->name ?? 'Sin Nombre',
                        'total_quantity_sum' => 0,
                        'post_name' => '',
                        'post_created_at' => '',
                        'post_finish_at' => '',
                        'post_count' => '',
                        'post_cajas_hora' => 'N/A',
                        'product_name' => '',
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
             return null;
        }
    }
    /**
     * @OA\Get(
     *     path="/api/workers-export/generate-excel",
     *     tags={"WorkersExport"},
     *     summary="Genera un archivo Excel con el informe de operadores agrupado",
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=true,
     *         description="Fecha de inicio en formato YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=true,
     *         description="Fecha de fin en formato YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="filter_posts",
     *         in="query",
     *         required=false,
     *         description="Si es true, solo incluye operadores con puestos activos",
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Descarga del archivo Excel",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     ),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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

        $excelData = $this->getProcessedOperatorData($fromDate, $toDate, $filterPosts);

        if ($excelData === null) {
             return response()->json(['message' => 'Internal server error while processing data.'], 500);
        }

        $fileNameDatePart = $fromDate . '_a_' . $toDate;
        $filterSuffix = $filterPosts ? '_ConPuestosActivos' : '_Todos';
        $fileName = 'Informe_Operadores_' . $fileNameDatePart . $filterSuffix . '_Backend_Agrupado.xlsx';
        Log::info('generateExcelStandalone: Filename generated.', ['filename' => $fileName]);

        try {
            if (!class_exists(WorkersStandaloneExport::class)) {
                 Log::critical('generateExcelStandalone: Export class WorkersStandaloneExport does not exist.');
                 return response()->json(['message' => 'Internal server error: Export class missing.'], 500);
            }
            return Excel::download(new WorkersStandaloneExport($excelData), $fileName);
        } catch (\Exception $e) {
            Log::error('generateExcelStandalone: FINAL EXCEPTION during Excel generation/download.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error interno del servidor al generar el archivo Excel final.'], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/workers-export/generate-pdf",
     *     tags={"WorkersExport"},
     *     summary="Genera un archivo PDF con el informe de operadores agrupado",
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=true,
     *         description="Fecha de inicio en formato YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=true,
     *         description="Fecha de fin en formato YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="filter_posts",
     *         in="query",
     *         required=false,
     *         description="Si es true, solo incluye operadores con puestos activos",
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Descarga del archivo PDF",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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

        $pdfData = $this->getProcessedOperatorData($fromDate, $toDate, $filterPosts);

        if ($pdfData === null) {
             return response()->json(['message' => 'Internal server error while processing data.'], 500);
        }

        $fileNameDatePart = $fromDate . '_a_' . $toDate;
        $filterSuffix = $filterPosts ? '_ConPuestosActivos' : '_Todos';
        $fileName = 'Informe_Operadores_' . $fileNameDatePart . $filterSuffix . '_Backend_Agrupado.pdf';
        Log::info('generatePdfStandalone: Filename generated.', ['filename' => $fileName]);

        try {
            $pdf = Pdf::loadView('exports.workers_pdf', [
                'data' => $pdfData,
                'fromDate' => $fromDate,
                'toDate' => $toDate
            ]);
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('generatePdfStandalone: EXCEPTION during PDF generation/download.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Internal server error generating the PDF file.'], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/workers-export/send-reports-by-email",
     *     tags={"WorkersExport"},
     *     summary="Envía por correo los informes de operadores para la fecha actual",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", description="Correo electrónico del destinatario"),
     *             @OA\Property(property="filter_posts", type="boolean", description="Si es true, solo incluye operadores con puestos activos", default=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Correo programado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function sendReportsByEmail(Request $request)
    {
        ignore_user_abort(true);
        Log::info('sendReportsByEmail: METHOD STARTED.');
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'filter_posts' => 'sometimes|in:true,false',
        ]);
        if ($validator->fails()) {
            Log::error('sendReportsByEmail: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $recipientEmail = $validator->validated()['email'];
        $reportDate = Carbon::today()->format('Y-m-d');
        $filterPosts = $request->query('filter_posts', 'true') === 'true';
        Log::info('sendReportsByEmail: Parameters validated.', ['email' => $recipientEmail, 'date' => $reportDate, 'filter' => $filterPosts]);
        try {
            $queryParams = http_build_query([
                'from_date' => $reportDate,
                'to_date' => $reportDate,
                'filter_posts' => $filterPosts ? 'true' : 'false'
            ]);
            $baseUrl = rtrim(config('app.url'), '/');
            if (!$baseUrl) {
                 Log::error('sendReportsByEmail: APP_URL no está configurada en .env.');
                 throw new \Exception('APP_URL configuration is missing.');
            }
            $excelUrl = $baseUrl . '/api/workers-export/generate-excel?' . $queryParams;
            $pdfUrl = $baseUrl . '/api/workers-export/generate-pdf?' . $queryParams;
            $webInterfaceUrl = $baseUrl . '/workers/export.html';
            Log::info('sendReportsByEmail: Download and Web URLs constructed.', [
                'excel' => $excelUrl, 'pdf' => $pdfUrl, 'web' => $webInterfaceUrl
            ]);
        } catch (\Exception $e) {
             Log::error('sendReportsByEmail: EXCEPTION during URL construction.', [
                'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
             ]);
             return response()->json(['message' => 'Internal server error constructing report URLs.'], 500);
        }
        $useQueue = true;
        Log::info('sendReportsByEmail: Attempting to ' . ($useQueue ? 'queue' : 'send synchronously') . ' email.', ['recipient' => $recipientEmail]);
        try {
             if (!class_exists(OperatorReportMail::class)) {
                 Log::critical('sendReportsByEmail: Mailable class OperatorReportMail does not exist.');
                 return response()->json(['message' => 'Internal server error: Mailable class missing.'], 500);
            }
            $mailable = new OperatorReportMail($excelUrl, $pdfUrl, $webInterfaceUrl, $reportDate);
            if ($useQueue) {
                 Mail::to($recipientEmail)->queue($mailable);
                 Log::info('sendReportsByEmail: Email successfully queued.', ['recipient' => $recipientEmail]);
                 $successMessage = 'El correo con los informes para ' . $reportDate . ' ha sido programado para envío a ' . $recipientEmail;
            } else {
                 Mail::to($recipientEmail)->send($mailable);
                 Log::info('sendReportsByEmail: Email sent successfully (synchronously).', ['recipient' => $recipientEmail]);
                 $successMessage = 'El correo con los informes para ' . $reportDate . ' ha sido enviado a ' . $recipientEmail;
            }
            return response()->json(['success' => true, 'message' => $successMessage]);
        } catch (\Exception $e) {
            Log::error('sendReportsByEmail: EXCEPTION while ' . ($useQueue ? 'queueing' : 'sending synchronously') . ' email.', [
                'recipient' => $recipientEmail, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error interno del servidor al intentar enviar el correo.'], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/workers-export/complete-list",
     *     tags={"WorkersExport"},
     *     summary="Obtiene la lista completa de operadores con sus posts en el rango de fechas",
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         required=false,
     *         description="Fecha de inicio en formato YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         required=false,
     *         description="Fecha de fin en formato YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de operadores con posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Operator")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
     public function completeList(Request $request)
     {
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
    /**
     * @OA\Post(
     *     path="/api/workers-export/send-assignment-list-by-email",
     *     tags={"WorkersExport"},
     *     summary="Envía por correo el listado de asignación de puestos",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", description="Correo electrónico del destinatario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Correo de asignación programado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Errores de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function sendAssignmentListByEmail(Request $request)
    {
        ignore_user_abort(true);
        Log::info('sendAssignmentListByEmail: METHOD STARTED.');
        $validator = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validator->fails()) {
            Log::error('sendAssignmentListByEmail: Validation failed.', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $recipientEmail = $validator->validated()['email'];
        Log::info("sendAssignmentListByEmail: Email validated ({$recipientEmail}).");
        try {
            $baseUrl = rtrim(config('app.url'), '/');
            $assignmentUrl = $baseUrl . '/confeccion-puesto-listado/';
            Log::info('sendAssignmentListByEmail: Assignment URL constructed.', ['url' => $assignmentUrl]);
        } catch (\Exception $e) {
            Log::error('sendAssignmentListByEmail: EXCEPTION building URL.', [
                'message' => $e->getMessage(), 'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error interno construyendo la URL.'], 500);
        }
        try {
            $mailable = new AssignmentListMail($assignmentUrl);
            Mail::to($recipientEmail)->queue($mailable);
            Log::info('sendAssignmentListByEmail: Email queued.', ['recipient' => $recipientEmail]);
            return response()->json([
                'success' => true,
                'message' => "El Listado de Asignación ha sido programado para envío a {$recipientEmail}.",
            ]);
        } catch (\Exception $e) {
            Log::error('sendAssignmentListByEmail: EXCEPTION sending email.', [
                'recipient' => $recipientEmail, 'message'   => $e->getMessage(), 'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error interno al enviar el correo.'], 500);
        }
    }
}
