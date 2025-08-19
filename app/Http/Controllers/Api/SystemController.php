<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Importa DB correctamente
//anadir log
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{

    private function validateToken(Request $request)
    {
            // Intentar obtener el token primero desde el encabezado
        $token = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        // Si no está en el encabezado, buscar en el cuerpo de la solicitud
        if (!$token) {
            $token = $request->input('token');
        }

        if ($token !== env('TOKEN_SYSTEM')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token de autorización inválido. Token recibido: ' . ($token ?? 'null'),
            ], 403);
        }

        return null; // Token válido
    }

/**
 * @OA\Post(
 *     path="/api/reboot",
 *     summary="Reiniciar el sistema",
 *     tags={"Sistema"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", description="Token de autorización")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="El sistema se está reiniciando.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al intentar reiniciar el sistema.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

    public function rebootSystem(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /var/www/html/reboot-system.sh';
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'El sistema se está reiniciando.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

/**
 * @OA\Post(
 *     path="/api/poweroff",
 *     summary="Apagar el sistema",
 *     tags={"Sistema"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", description="Token de autorización")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="El sistema se está apagando.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al intentar apagar el sistema.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

    public function powerOffSystem(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /var/www/html/poweroff-system.sh';
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'El sistema se está apagando.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
/**
 * @OA\Get(
 *     path="/api/server-stats",
 *     summary="Obtener estadísticas del servidor",
 *     tags={"Sistema"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="Authorization",
 *         in="header",
 *         required=true,
 *         @OA\Schema(type="string", example="Token de autorización"),
 *         description="Token de autorización '"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Estadísticas del servidor obtenidas con éxito.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="cpu_usage", type="string", example="15%"),
 *             @OA\Property(property="ram_usage", type="string", example="1024MB/4096MB")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
 *         )
 *     )
 * )
 */


    public function getServerStats(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            // Comando para obtener uso de CPU
            $cpuCommand = "top -bn1 | grep 'Cpu(s)' | awk '{print $2 + $4}'";
            $cpuProcess = Process::fromShellCommandline($cpuCommand);
            $cpuProcess->run();

            // Comando para obtener uso de RAM
            $ramCommand = "free -m | awk '/Mem:/ { print $3\"MB/\"$2\"MB\" }'";
            $ramProcess = Process::fromShellCommandline($ramCommand);
            $ramProcess->run();

            if (!$cpuProcess->isSuccessful() || !$ramProcess->isSuccessful()) {
                throw new ProcessFailedException($cpuProcess);
            }

            $cpuUsage = trim($cpuProcess->getOutput());
            $ramUsage = trim($ramProcess->getOutput());

            return response()->json([
                'status' => 'success',
                'cpu_usage' => $cpuUsage . '%',
                'ram_usage' => $ramUsage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

/**
 * @OA\Post(
 *     path="/api/restart-supervisor",
 *     summary="Reiniciar todos los procesos de Supervisor",
 *     tags={"Supervisor"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", description="Token de autorización")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Supervisor reiniciado con éxito.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al reiniciar Supervisor.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */


    public function restartSupervisor(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /usr/bin/supervisorctl restart all';
            $process = Process::fromShellCommandline($command);
            $process->run();

            // Registrar salida del proceso
            Log::info("Salida del proceso: " . $process->getOutput());
            Log::info("Errores del proceso: " . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Supervisor reiniciado con éxito.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al reiniciar Supervisor: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
/**
 * @OA\Post(
 *     path="/api/start-supervisor",
 *     summary="Iniciar todos los procesos de Supervisor",
 *     tags={"Supervisor"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", description="Token de autorización")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Supervisor iniciado con éxito.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al iniciar Supervisor.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

    public function startSupervisor(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /usr/bin/supervisorctl start all';
            $process = Process::fromShellCommandline($command);
            $process->run();

            // Registrar salida del proceso
            Log::info("Salida del proceso: " . $process->getOutput());
            Log::info("Errores del proceso: " . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Supervisor iniciado con éxito.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al iniciar Supervisor: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
 /**
 * @OA\Post(
 *     path="/api/stop-supervisor",
 *     summary="Detener todos los procesos de Supervisor",
 *     tags={"Supervisor"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", description="Token de autorización")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Supervisor apagado con éxito.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al apagar Supervisor.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */

    public function stopSupervisor(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /usr/bin/supervisorctl stop all';
            $process = Process::fromShellCommandline($command);
            $process->run();

            // Registrar salida del proceso
            Log::info("Salida del proceso: " . $process->getOutput());
            Log::info("Errores del proceso: " . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Supervisor apagado con éxito.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al apagar Supervisor: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

/**
 * @OA\Post(
 *     path="/api/restart-485-Swift",
 *     summary="Reiniciar el dispositivo SWIFT 485",
 *     tags={"Dispositivos"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", description="Token de autorización", example="TU_TOKEN_DE_AUTORIZACION")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="SWIFT 485 reiniciado con éxito.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="SWIFT 485 reiniciado con éxito.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al reiniciar SWIFT 485.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Error al reiniciar SWIFT 485. Detalles: Permiso denegado.")
 *         )
 *     )
 * )
 */


    public function restart485Swift(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /bin/systemctl restart 485.service';
            $process = Process::fromShellCommandline($command);
            $process->run();

            // Registrar salida del proceso
            Log::info("Salida del proceso: " . $process->getOutput());
            Log::info("Errores del proceso: " . $process->getErrorOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'SWIFT 485  reiniciado con éxito.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al reiniciar SWIFT 485: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function runUpdateScript(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /var/www/html/update.sh';
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // Ajusta el tiempo de espera según sea necesario
            $process->run();

            // Registrar salida del proceso
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'El script de actualización se ejecutó correctamente.',
                'output' => $output,
                'errorOutput' => $errorOutput,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/supervisor-status",
     *     summary="Obtener el estado de todos los procesos de Supervisor",
     *     tags={"Supervisor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estado de Supervisor obtenido con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="supervisor_status", type="array", @OA\Items(type="string", example="program_name RUNNING pid 1234"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener el estado de Supervisor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al obtener el estado de Supervisor.")
     *         )
     *     )
     * )
     */

    public function getSupervisorStatus(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'sudo /usr/bin/supervisorctl status';
            $process = Process::fromShellCommandline($command);
            $process->run();

            // Incluso si hay procesos en estado de error, supervisorctl devuelve un código de salida 0 (exitoso)
            // Por lo tanto, siempre procesamos la salida, independientemente del código de salida
            $output = trim($process->getOutput());
            
            // Si no hay salida, consideramos que hay un problema con supervisor
            if (empty($output)) {
                Log::warning("Supervisor no devuelve datos. Posible problema con el servicio.");
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Supervisor no devuelve datos. Posible problema con el servicio.',
                    'supervisor_status' => [],
                    'processes' => [],
                    'has_failed_processes' => false
                ]);
            }
            
            // Dividir la salida en líneas
            $outputLines = explode("\n", $output);
            
            // Procesar cada línea para extraer información estructurada
            $processes = [];
            $hasFailedProcesses = false;
            
            foreach ($outputLines as $line) {
                if (empty(trim($line))) continue;
                
                // Patrón típico: nombre_proceso                  RUNNING   pid 12345, uptime 0:01:23
                // O en caso de error: nombre_proceso                  FATAL     Exited too quickly (process log may have details)
                $pattern = '/^(\S+)\s+(\w+)\s+(.*)$/'; // Nombre, estado, detalles
                
                if (preg_match($pattern, $line, $matches)) {
                    $processName = $matches[1];
                    $status = $matches[2];
                    $details = $matches[3];
                    
                    // Determinar si el proceso está en estado de error
                    $isError = in_array(strtoupper($status), ['FATAL', 'BACKOFF', 'EXITED', 'STOPPED', 'UNKNOWN']);
                    
                    if ($isError) {
                        $hasFailedProcesses = true;
                    }
                    
                    $processes[] = [
                        'name' => $processName,
                        'status' => $status,
                        'details' => $details,
                        'is_error' => $isError
                    ];
                } else {
                    // Si la línea no coincide con el patrón esperado, la agregamos como está
                    $processes[] = [
                        'name' => 'unknown',
                        'status' => 'UNKNOWN',
                        'details' => $line,
                        'is_error' => true
                    ];
                    $hasFailedProcesses = true;
                }
            }
            
            return response()->json([
                'status' => $hasFailedProcesses ? 'warning' : 'success',
                'supervisor_status' => $outputLines, // Mantener la compatibilidad con el formato anterior
                'processes' => $processes,           // Nuevo formato estructurado
                'has_failed_processes' => $hasFailedProcesses
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener estado de Supervisor: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'supervisor_status' => [],
                'processes' => [],
                'has_failed_processes' => false
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/check-485-service",
     *     summary="Verificar el estado del servicio 485.service",
     *     tags={"Dispositivos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estado del servicio 485.service obtenido con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Servicio encontrado"),
     *             @OA\Property(property="service_status", type="string", example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al verificar el estado del servicio.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al verificar el estado del servicio.")
     *         )
     *     )
     * )
     */
    public function check485Service(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'systemctl is-active 485.service || echo "not_installed"';
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());
            $status = $output === "not_installed" ? "not_installed" : $output;

            return response()->json([
                'status' => 'success',
                'message' => $status === "not_installed" ? "Servicio no instalado" : "Servicio encontrado",
                'service_status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al verificar el servicio 485: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/install-485-service",
     *     summary="Crear y arrancar el servicio 485.service",
     *     tags={"Dispositivos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Servicio 485 creado y arrancado con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Servicio creado y arrancado con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al crear o arrancar el servicio.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al crear o arrancar el servicio.")
     *         )
     *     )
     * )
     */
    public function install485Service(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $serviceFilePath = "/etc/systemd/system/485.service";
            $serviceDefinition = "[Unit]
    Description=Swift 485 Service
    After=network.target

    [Service]
    ExecStart=/usr/bin/python3 /var/www/html/485.py
    Restart=always
    User=root
    Group=root

    [Install]
    WantedBy=multi-user.target";

            if (!file_exists($serviceFilePath)) {
                file_put_contents($serviceFilePath, $serviceDefinition);
            }

            $commands = [
                'systemctl daemon-reload',
                'systemctl enable 485.service',
                'systemctl start 485.service',
            ];

            foreach ($commands as $command) {
                $process = Process::fromShellCommandline($command);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Servicio 485 creado y arrancado con éxito.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al crear el servicio 485: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/app-update",
     *     summary="Actualizar la aplicación ejecutando el script update.sh",
     *     tags={"Aplicación"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Actualización iniciada correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Script de actualización ejecutado correctamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al ejecutar el script de actualización.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al ejecutar el script de actualización.")
     *         )
     *     )
     * )
     */
    public function appUpdate(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            // Registrar inicio de la actualización
            Log::info("Iniciando proceso de actualización de la aplicación");
            
            // Verificar si el script existe
            if (!file_exists('/var/www/html/update.sh')) {
                Log::error("El script de actualización no existe en la ruta especificada");
                return response()->json([
                    'status' => 'error',
                    'message' => 'El script de actualización no existe en la ruta especificada.',
                    'details' => null,
                    'output' => null
                ], 500);
            }
            
            // Verificar permisos del script
            if (!is_executable('/var/www/html/update.sh')) {
                Log::warning("El script de actualización no tiene permisos de ejecución");
                // Intentar corregir los permisos
                $chmodProcess = Process::fromShellCommandline('sudo chmod +x /var/www/html/update.sh');
                $chmodProcess->run();
                
                if (!$chmodProcess->isSuccessful()) {
                    Log::error("No se pudieron establecer permisos de ejecución en el script");
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No se pudieron establecer permisos de ejecución en el script de actualización.',
                        'details' => $chmodProcess->getErrorOutput(),
                        'output' => null
                    ], 500);
                }
            }
            
            // Ejecutar el script con un timeout extendido
            $command = 'sudo /var/www/html/update.sh';
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(900); // 15 minutos para permitir actualizaciones más largas
            
            // Capturar la salida en tiempo real para logging
            $output = [];
            $capturedOutput = '';
            
            $process->run(function ($type, $buffer) use (&$output, &$capturedOutput) {
                $lines = explode("\n", $buffer);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        if ($type === Process::ERR) {
                            Log::warning("[UPDATE-STDERR] " . $line);
                        } else {
                            Log::info("[UPDATE-STDOUT] " . $line);
                        }
                        $output[] = $line;
                        $capturedOutput .= $line . "\n";
                    }
                }
            });

            // Analizar el resultado
            if (!$process->isSuccessful()) {
                $errorMsg = "Error al ejecutar el script de actualización. Código de salida: " . $process->getExitCode();
                Log::error($errorMsg);
                Log::error("Error detallado: " . $process->getErrorOutput());
                
                return response()->json([
                    'status' => 'error',
                    'message' => $errorMsg,
                    'exit_code' => $process->getExitCode(),
                    'details' => $process->getErrorOutput(),
                    'output' => $output
                ], 500);
            }

            // Verificar si hay mensajes de error en la salida aunque el script haya terminado con éxito
            $errorKeywords = ['error', 'exception', 'failed', 'fatal', 'cannot', 'unable to'];
            $hasErrorInOutput = false;
            $errorLines = [];
            
            foreach ($output as $line) {
                foreach ($errorKeywords as $keyword) {
                    if (stripos($line, $keyword) !== false) {
                        $hasErrorInOutput = true;
                        $errorLines[] = $line;
                        break;
                    }
                }
            }
            
            if ($hasErrorInOutput) {
                Log::warning("El script de actualización se completó con código de éxito pero contiene mensajes de error", [
                    'error_lines' => $errorLines
                ]);
                
                return response()->json([
                    'status' => 'warning',
                    'message' => 'La actualización se completó con advertencias.',
                    'warnings' => $errorLines,
                    'output' => $output
                ]);
            }

            // Todo salió bien
            Log::info("Script de actualización ejecutado correctamente");
            return response()->json([
                'status' => 'success',
                'message' => 'Script de actualización ejecutado correctamente.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error("Excepción al ejecutar el script de actualización: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso de actualización: ' . $e->getMessage(),
                'details' => $e->getTraceAsString(),
                'output' => isset($output) ? $output : null
            ], 500);
        }
    }
/**
 * @OA\Post(
 *     path="/api/verne-update",
 *     summary="Actualizar VerneMQ ejecutando el script verne.sh",
 *     tags={"Aplicación"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Actualización de VerneMQ iniciada correctamente.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Script de actualización de VerneMQ ejecutado correctamente.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Token de autorización inválido.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al ejecutar el script de actualización de VerneMQ.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Error al ejecutar el script de actualización de VerneMQ.")
 *         )
 *     )
 * )
 */
    public function verneUpdate(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = 'cd /var/www/html && sudo ./verne.sh';
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // Configurar tiempo máximo para el script
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Script de actualización de VerneMQ ejecutado correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al ejecutar el script de actualización de VerneMQ: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/server-ips",
     *     summary="Obtener las direcciones IP del servidor",
     *     tags={"Servidor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Direcciones IP obtenidas con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="ips", type="array", @OA\Items(type="string", example="192.168.1.100"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener las direcciones IP.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al obtener las direcciones IP.")
     *         )
     *     )
     * )
     */
    public function getServerIps(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            $command = "ip -o -4 addr list | awk '{print $4}' | cut -d/ -f1";
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = trim($process->getOutput());
            $ips = array_filter(explode("\n", $output)); // Convertir a array y eliminar líneas vacías

            return response()->json([
                'status' => 'success',
                'ips' => $ips,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener las direcciones IP del servidor: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/update-env",
     *     summary="Actualizar configuraciones en el archivo .env",
     *     tags={"Servidor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mysql_server", type="string", example="127.0.0.1"),
     *             @OA\Property(property="mysql_port", type="string", example="3306"),
     *             @OA\Property(property="mysql_db", type="string", example="my_database"),
     *             @OA\Property(property="mysql_user", type="string", example="root"),
     *             @OA\Property(property="mysql_password", type="string", example="secure_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración actualizada correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Configuración actualizada correctamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Token de autorización inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al actualizar el archivo .env.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Error al actualizar el archivo .env.")
     *         )
     *     )
     * )
     */

    public function updateEnv(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }
    
        $envUpdates = [
            'MYSQL_SERVER' => $request->input('mysql_server'),
            'MYSQL_PORT' => $request->input('mysql_port'),
            'MYSQL_DB' => $request->input('mysql_db'),
            'MYSQL_TABLE_LINE' => $request->input('mysql_table_line'),
            'MYSQL_TABLE_SENSOR' => $request->input('mysql_table_sensor'),
            'MYSQL_USER' => $request->input('mysql_user'),
            'MYSQL_PASSWORD' => $request->input('mysql_password'),
        ];
    
        try {
            $envPath = base_path('.env');
            if (!file_exists($envPath)) {
                throw new \Exception("Archivo .env no encontrado.");
            }
    
            $envContent = file_get_contents($envPath);
    
            foreach ($envUpdates as $key => $value) {
                if (preg_match("/^{$key}=.*/m", $envContent)) {
                    // Si la clave existe, reemplazar el valor
                    $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
                } else {
                    // Si la clave no existe, añadirla al final del archivo
                    $envContent .= PHP_EOL . "{$key}={$value}";
                }
            }
    
            file_put_contents($envPath, $envContent);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Configuración actualizada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar el archivo .env: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * @OA\Post(
     *     path="/api/check-db-connection",
     *     summary="Verificar la conexión a la base de datos MySQL",
     *     tags={"Base de Datos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mysql_server", type="string", example="127.0.0.1"),
     *             @OA\Property(property="mysql_port", type="string", example="3306"),
     *             @OA\Property(property="mysql_db", type="string", example="my_database"),
     *             @OA\Property(property="mysql_user", type="string", example="root"),
     *             @OA\Property(property="mysql_password", type="string", example="secure_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conexión realizada con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Conexión realizada con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al conectar con la base de datos.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No se pudo conectar a la base de datos.")
     *         )
     *     )
     * )
     */
    public function checkDbConnection(Request $request)
    {
        try {
            $connection = new \PDO(
                "mysql:host={$request->input('mysql_server')};port={$request->input('mysql_port')};dbname={$request->input('mysql_db')}",
                $request->input('mysql_user'),
                $request->input('mysql_password')
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Conexión realizada con éxito.',
            ]);
        } catch (\PDOException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error en la conexión: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/verify-and-sync-database",
     *     summary="Verificar y sincronizar la base de datos externa",
     *     tags={"Base de Datos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sincronización realizada con éxito.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Base de datos verificada y sincronizada correctamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al verificar o sincronizar la base de datos.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Detalles del error.")
     *         )
     *     )
     * )
     */
    public function verifyAndSyncDatabase()
    {
        // Variables de entorno
        $host = env('MYSQL_SERVER');
        $port = env('MYSQL_PORT');
        $database = env('MYSQL_DB');
        $user = env('MYSQL_USER');
        $password = env('MYSQL_PASSWORD');
    
        $tableLineaPorOrden = env('MYSQL_TABLE_LINE');
        $tableSensoresPorOrden = env('MYSQL_TABLE_SENSOR');
    
        // Validar que las variables existen
        if (!$host || !$port || !$database || !$user || !$password || !$tableLineaPorOrden || !$tableSensoresPorOrden) {
            Log::error('Faltan variables de entorno para la base de datos externa.');
            return response()->json([
                'status' => 'error',
                'message' => 'Faltan variables de entorno para la base de datos externa.'
            ], 500);
        }
    
        // Crear la base de datos si no existe
        try {
            $pdo = new \PDO("mysql:host=$host;port=$port", $user, $password);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            Log::info("Base de datos '$database' verificada/creada correctamente.");
        } catch (\PDOException $e) {
            Log::error('Error al crear/verificar la base de datos: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear/verificar la base de datos: ' . $e->getMessage()
            ], 500);
        }
    
        // Probar conexión a la base de datos
        try {
            $connection = DB::connection('external');
            $connection->getPdo();
        } catch (\Exception $e) {
            Log::error('Error al conectar con la base de datos externa: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al conectar con la base de datos externa: ' . $e->getMessage()
            ], 500);
        }
    
        // Tablas y columnas esperadas
        $expectedTables = [
            $tableLineaPorOrden => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'IdLinea' => 'VARCHAR(255)',
                'IdOrden' => 'VARCHAR(255)',
                'IdReference' => 'VARCHAR(255)',
                'ShiftCount' => 'INT',
                'OrderCount' => 'DECIMAL(10,2)',
                'OrderUnit' => 'VARCHAR(255)',
                'UnitsPerBox' => 'DECIMAL(10,2)', // Nueva columna añadida
                'SensorCount' => 'DECIMAL(10,2)',
                'UmaCount' => 'DECIMAL(10,2)',
                'StartAt' => 'TIMESTAMP',
                'FinishAt' => 'TIMESTAMP',
                'TimeON' => 'TIME',
                'TimeDown' => 'TIME',
                'TimeSlow' => 'TIME',
                'TimePreparing' => 'TIME',
                'customerId' => 'VARCHAR(255)',        // Nueva columna que acepta números y letras
                'TimeDownSensors' => 'TIME',           // Nueva columna de tipo TIME
            ],
            $tableSensoresPorOrden => [
                'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                'IdOrder' => 'VARCHAR(255)',
                'IdReference' => 'VARCHAR(255)',
                'StartAt' => 'TIMESTAMP',
                'FinishAt' => 'TIMESTAMP',
                'IdClient' => 'VARCHAR(255)',
                'IdSensor' => 'VARCHAR(255)',
                'TcTheoretical' => 'DECIMAL(10,2)',
                'TcUnit' => 'VARCHAR(255)',
                'TcAverage' => 'DECIMAL(10,2)',
                'TcMin' => 'DECIMAL(10,2)',
                'IdLine' => 'VARCHAR(255)',
                'TimeOn' => 'TIME',
                'TimeDown' => 'TIME',
                'TimeSlow' => 'TIME',
                'SensorCount' => 'DECIMAL(10,2)',
                'SensorUnitCount' => 'VARCHAR(255)',
                'SensorWeight' => 'DECIMAL(10,2)',
                'SensorUnitWeight' => 'VARCHAR(255)',
                'GrossWeight01' => 'DECIMAL(10,2)', // Nuevo campo
                'GrossWeight02' => 'DECIMAL(10,2)', // Nuevo campo
                'customerId' => 'VARCHAR(255)',     // Nueva columna que acepta números y letras
            ]
        ];
    
        // Validar y crear tablas/columnas si no existen
        foreach ($expectedTables as $table => $columns) {
            if (!$connection->getSchemaBuilder()->hasTable($table)) {
                $this->createTable($connection, $table, $columns);
            } else {
                $this->validateAndAddColumns($connection, $table, $columns);
            }
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Base de datos verificada y sincronizada correctamente.'
        ]);
    }
    
    
    private function createTable($connection, $table, $columns)
    {
        $schema = $connection->getSchemaBuilder();
        $schema->create($table, function ($tableBlueprint) use ($columns) {
            foreach ($columns as $column => $definition) {
                if ($column === 'id') {
                    $tableBlueprint->increments('id'); // Clave primaria autoincremental
                } else {
                    // Analizar el tipo de dato del esquema
                    $type = strtoupper(explode('(', $definition)[0]);
                    switch ($type) {
                        case 'VARCHAR':
                            $length = 255; // Longitud predeterminada
                            if (preg_match('/\((\d+)\)/', $definition, $matches)) {
                                $length = (int) $matches[1];
                            }
                            $tableBlueprint->string($column, $length)->nullable();
                            break;
                        case 'INT':
                            $tableBlueprint->integer($column)->nullable();
                            break;
                        case 'DECIMAL':
                            $precision = 10;
                            $scale = 2;
                            if (preg_match('/\((\d+),(\d+)\)/', $definition, $matches)) {
                                $precision = (int) $matches[1];
                                $scale = (int) $matches[2];
                            }
                            $tableBlueprint->decimal($column, $precision, $scale)->nullable();
                            break;
                        case 'TIMESTAMP':
                            $tableBlueprint->timestamp($column)->nullable();
                            break;
                        case 'TIME':
                            $tableBlueprint->time($column)->nullable();
                            break;
                        default:
                            throw new \Exception("Tipo de dato no soportado: $type");
                    }
                }
            }
        });
        Log::info("Tabla creada: $table");
    }    
    private function validateAndAddColumns($connection, $table, $columns)
    {
        $schema = $connection->getSchemaBuilder();
        foreach ($columns as $column => $definition) {
            if (!$schema->hasColumn($table, $column)) {
                $schema->table($table, function ($tableBlueprint) use ($column, $definition) {
                    // Analizar el tipo de dato del esquema
                    $type = strtoupper(explode('(', $definition)[0]);
                    switch ($type) {
                        case 'VARCHAR':
                            $length = 255; // Longitud predeterminada
                            if (preg_match('/\((\d+)\)/', $definition, $matches)) {
                                $length = (int) $matches[1];
                            }
                            $tableBlueprint->string($column, $length)->nullable();
                            break;
                        case 'INT':
                            $tableBlueprint->integer($column)->nullable();
                            break;
                        case 'DECIMAL':
                            $precision = 10;
                            $scale = 2;
                            if (preg_match('/\((\d+),(\d+)\)/', $definition, $matches)) {
                                $precision = (int) $matches[1];
                                $scale = (int) $matches[2];
                            }
                            $tableBlueprint->decimal($column, $precision, $scale)->nullable();
                            break;
                        case 'TIMESTAMP':
                            $tableBlueprint->timestamp($column)->nullable();
                            break;
                        case 'TIME':
                            $tableBlueprint->time($column)->nullable();
                            break;
                        default:
                            throw new \Exception("Tipo de dato no soportado: $type");
                    }
                });
                Log::info("Columna agregada: $column en la tabla $table");
            }
        }
    }      

    /**
     * @OA\Post(
     *     path="/api/restart-mysql",
     *     summary="Reiniciar el servicio MySQL/Percona",
     *     tags={"Sistema"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", description="Token de autorización")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="El servicio MySQL/Percona se está reiniciando.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token de autorización inválido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al intentar reiniciar MySQL/Percona.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function restartMysql(Request $request)
    {
        $validation = $this->validateToken($request);
        if ($validation) {
            return $validation;
        }

        try {
            // Registrar en el log que se está reiniciando MySQL
            Log::info('Reiniciando servicio MySQL/Percona por solicitud del usuario');
            
            // Comando para reiniciar MySQL/Percona
            $command = 'sudo systemctl restart mysql';
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Error al reiniciar MySQL/Percona: ' . $process->getErrorOutput());
                throw new ProcessFailedException($process);
            }

            Log::info('Servicio MySQL/Percona reiniciado correctamente');
            return response()->json([
                'status' => 'success',
                'message' => 'El servicio MySQL/Percona se está reiniciando.',
            ]);
        } catch (\Exception $e) {
            Log::error('Excepción al reiniciar MySQL/Percona: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/fix-logs",
     *     summary="Corregir permisos de logs",
     *     tags={"Sistema"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", description="Token de autorización")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permisos de logs corregidos con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Permisos de logs corregidos con éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al corregir permisos de logs",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Error al corregir permisos de logs")
     *         )
     *     )
     * )
     */
    public function fixLogs(Request $request)
    {
        // Validar token
        $tokenValidation = $this->validateToken($request);
        if ($tokenValidation) {
            return $tokenValidation;
        }

        try {
            // Ejecutar el script para corregir permisos
            $process = Process::fromShellCommandline('sudo /var/www/html/fix_log_permissions.sh');
            $process->setTimeout(60);
            $process->run();

            // Verificar si el proceso se ejecutó correctamente
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            Log::info('Permisos de logs corregidos manualmente por un usuario');

            return response()->json([
                'status' => 'success',
                'message' => 'Permisos de logs corregidos con éxito',
                'output' => $process->getOutput()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al corregir permisos de logs: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al corregir permisos de logs: ' . $e->getMessage()
            ], 500);
        }
    }
}
