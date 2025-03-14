<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HostList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\HostMonitorAlert;

class CheckHostMonitor extends Command
{
    private $baseUrl = 'http://localhost:3006';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostmonitor:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de alerta si un host no tiene registros en host_monitors en los últimos 3 minutos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando verificación de hosts...");

        // Bucle infinito: se recomienda poner este comando en Supervisor para que siempre esté en ejecución.
        while (true) {
            // Obtener todos los hosts
            $hosts = HostList::all();

            foreach ($hosts as $host) {
                $this->info("Verificando host: {$host->name}");
                // Obtener el último registro de host_monitors para este host
                $lastMonitor = $host->hostMonitors()->latest()->first();

                // Si no existe registro o el último es anterior a hace 3 minutos
                if (!$lastMonitor || Carbon::parse($lastMonitor->created_at)->lt(Carbon::now()->subMinutes(3))) {
                    $cacheKey = "host_alert_sent_{$host->id}";

                    // Si no se ha enviado alerta previamente para este incidente
                    if (!Cache::has($cacheKey)) {
                        // Si el host tiene correos definidos
                        if ($host->emails) {
                            $emailList = array_map('trim', explode(',', $host->emails));
                            Mail::to($emailList)->send(new HostMonitorAlert($host));
                            $this->info("Alerta enviada por correo para el host {$host->name}");
                        }

                        // Si el host tiene teléfonos definidos
                        if ($host->phones) {
                            $phoneList = array_map('trim', explode(',', $host->phones));
                            foreach ($phoneList as $phone) {
                                $message = "⚠️ Alerta: No hay datos recientes para {$host->name}";
                                $this->sendWhatsApp($phone, $message);
                                $this->sendTelegram($phone, $message);
                            }
                        }

                        // Marcar alerta enviada en caché durante 1 hora
                        Cache::put($cacheKey, Carbon::now(), 36000);
                    } else {
                        $lastAlert = Carbon::parse(Cache::get($cacheKey));
                        $this->info("Alerta ya enviada recientemente para el host {$host->name}. Tiempo transcurrido: " . $lastAlert->diffForHumans(Carbon::now()));
                    }
                } else {
                    Cache::forget("host_alert_sent_{$host->id}");
                    $this->info("Borrado marca de alerta para el host {$host->id}.");
                }
            }

            $this->info("Esperando 2 minutos para la siguiente verificación...");
            sleep(120);
        }

        return 0;
    }

    private function sendWhatsApp($phone, $message)
    {
        \Log::info("message: {$message} telefono: {$phone}");
        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . "/api/send-message";

        try {
            $response = Http::withoutVerifying()->get($apiUrl, [
                'jid' => $phone . '@s.whatsapp.net',
                'message' => $message,
            ]);

            if ($response->successful()) {
               \Log::info('Mensaje enviado exitosamente');
            }

            \Log::error('No se pudo enviar el mensaje. Verifica el número y el mensaje.');
        } catch (\Exception $e) {
            \Log::error('No se pudo enviar el mensaje. Verifica el número y el mensaje.');
        }
    }
    
    private function sendTelegram($phone, $message)
    {
        \Log::info("message: {$message} telefono: {$phone}");
        try {
            $response = Http::post("{$this->baseUrl}/send-message/1/+{$phone}/{$message}");

            if ($response->successful()) {
                \Log::info('Mensaje enviado exitosamente');
            }
    
            \Log::error("message: {$message} telefono: {$phone} ");
        } catch (\Exception $e) {
            \Log::error("Error enviando mensaje de Telegram a {$phone}: " . $e->getMessage());
        }
    }
}
