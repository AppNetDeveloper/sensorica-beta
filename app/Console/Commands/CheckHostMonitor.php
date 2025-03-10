<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HostList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\HostMonitorAlert;

class CheckHostMonitor extends Command
{
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
                            // Convertir la cadena de correos (separados por comas) en un arreglo
                            $emailList = array_map('trim', explode(',', $host->emails));

                            // Enviar alerta
                            Mail::to($emailList)->send(new HostMonitorAlert($host));

                            $this->info("Alerta enviada para el host {$host->name}");

                            // Marcar alerta enviada en caché durante 1 hora
                            Cache::put($cacheKey, Carbon::now(), 36000);
                        } else {
                            $this->warn("El host {$host->name} no tiene correos configurados.");
                        }
                    } else {
                        // Si ya se envió alerta recientemente
                        $lastAlert = Carbon::parse(Cache::get($cacheKey));
                        $this->info("Alerta ya enviada recientemente para el host {$host->name}. Tiempo transcurrido: " . $lastAlert->diffForHumans(Carbon::now()));
                    }
                } else {
                    // Si se tiene registro reciente, borrar cualquier marca de alerta para que se pueda enviar nueva alerta en caso de que se interrumpa el flujo.
                    Cache::forget("host_alert_sent_{$host->id}");
                    $this->info("Borrado marca de alerta para el host {$host->id}.");
                }
            }

            $this->info("Esperando 2 minutos para la siguiente verificación...");
            sleep(120); // Esperar 120 segundos (2 minutos)
        }

        return 0;
    }
}
