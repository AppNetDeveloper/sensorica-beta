<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Http\Request;
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
}
