<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    private function validateToken(Request $request)
    {
        $token = $request->header('Authorization');
        if ($token !== env('TOKEN_SYSTEM')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token de autorización inválido.',
            ], 403);
        }
        return null;
    }

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
}
