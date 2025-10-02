<?php

namespace App\Console\Commands;

use App\Models\Maintenance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckOpenMaintenances extends Command
{
    protected $signature = 'maintenances:check-open';
    protected $description = 'Verifica mantenimientos abiertos y envía alertas si exceden el tiempo límite';

    public function handle()
    {
        $this->info('🔍 Verificando mantenimientos abiertos...');
        
        $now = Carbon::now();
        $warningThreshold = 12; // horas
        $criticalThreshold = 24; // horas
        
        // Mantenimientos sin iniciar después de 2 horas de creados
        $pendingMaintenances = Maintenance::whereNull('start_datetime')
            ->whereNull('end_datetime')
            ->where('created_at', '<=', $now->copy()->subHours(2))
            ->with(['customer', 'productionLine', 'operator'])
            ->get();
        
        foreach ($pendingMaintenances as $maintenance) {
            $hoursOpen = Carbon::parse($maintenance->created_at)->diffInHours($now);
            $this->sendAlert($maintenance, 'pending', $hoursOpen);
        }
        
        // Mantenimientos en curso
        $openMaintenances = Maintenance::whereNotNull('start_datetime')
            ->whereNull('end_datetime')
            ->with(['customer', 'productionLine', 'operator'])
            ->get();
        
        foreach ($openMaintenances as $maintenance) {
            $hoursOpen = Carbon::parse($maintenance->start_datetime)->diffInHours($now);
            
            if ($hoursOpen >= $criticalThreshold) {
                $this->sendAlert($maintenance, 'critical', $hoursOpen);
                $this->error("❌ Crítico: Mantenimiento #{$maintenance->id} abierto {$hoursOpen}h");
            } elseif ($hoursOpen >= $warningThreshold) {
                $this->sendAlert($maintenance, 'warning', $hoursOpen);
                $this->warn("⚠️  Advertencia: Mantenimiento #{$maintenance->id} abierto {$hoursOpen}h");
            }
        }
        
        $this->info("✅ Verificación completada. Pendientes: {$pendingMaintenances->count()}, En curso: {$openMaintenances->count()}");
        
        return 0;
    }
    
    private function sendAlert(Maintenance $maintenance, string $level, float $hours)
    {
        $customer = $maintenance->customer;
        $line = $maintenance->productionLine;
        $operator = $maintenance->operator;
        
        $levelEmoji = [
            'pending' => '⏳',
            'warning' => '⚠️',
            'critical' => '🚨',
        ];
        
        $levelText = [
            'pending' => 'PENDIENTE DE INICIAR',
            'warning' => 'ADVERTENCIA',
            'critical' => 'CRÍTICO',
        ];
        
        $message = sprintf(
            "%s MANTENIMIENTO %s\n\n" .
            "ID: #%d\n" .
            "Cliente: %s\n" .
            "Línea: %s\n" .
            "Operario: %s\n" .
            "Tiempo: %.1f horas\n" .
            "Estado: %s\n" .
            "Creado: %s",
            $levelEmoji[$level],
            $levelText[$level],
            $maintenance->id,
            $customer->name ?? 'N/A',
            $line->name ?? 'N/A',
            $operator->name ?? 'N/A',
            $hours,
            $level === 'pending' ? 'Sin iniciar' : 'En curso',
            $maintenance->created_at->format('Y-m-d H:i')
        );
        
        // WhatsApp notification
        try {
            $phones = array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_PHONE_MANTENIMIENTO', ''))));
            if (!empty($phones)) {
                $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/send-message';
                foreach ($phones as $phone) {
                    Http::withoutVerifying()->get($apiUrl, [
                        'jid' => $phone . '@s.whatsapp.net',
                        'message' => $message,
                    ]);
                }
                Log::info("WhatsApp alert sent for maintenance #{$maintenance->id}");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send WhatsApp alert: " . $e->getMessage());
        }
        
        // Telegram notification
        try {
            $peers = array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_MANTENIMIENTO_PEERS', ''))));
            if (!empty($peers)) {
                $baseUrl = 'http://localhost:3006';
                foreach ($peers as $peer) {
                    $peer = trim($peer);
                    $finalPeer = (str_starts_with($peer, '+') || str_starts_with($peer, '@')) ? $peer : ('+' . $peer);
                    $url = sprintf('%s/send-message/1/%s/%s', $baseUrl, rawurlencode($finalPeer), rawurlencode($message));
                    Http::post($url);
                }
                Log::info("Telegram alert sent for maintenance #{$maintenance->id}");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send Telegram alert: " . $e->getMessage());
        }
    }
}
