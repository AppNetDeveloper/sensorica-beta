<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperatorPost;
use Carbon\Carbon;

class FinalizeOperatorPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'operator-post:finalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finaliza los registros de los operadores a las 23:59:59 y los duplica al siguiente día.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Bucle infinito que ejecuta cada 20 segundos
        while (true) {
            try {
                // Obtener la fecha y hora actual
                $currentDate = Carbon::now();

                // Buscar solo los registros que no están finalizados (finish_at es null o vacío) y cuya fecha de creación no sea mayor que ahora
                $operatorPosts = OperatorPost::whereNull('finish_at')
                    ->orWhere('finish_at', '')
                    ->where('created_at', '<', Carbon::now()) // Filtramos para que 'created_at' no sea mayor a la fecha actual
                    ->get();

                foreach ($operatorPosts as $operatorPost) {
                    // Obtener el 'created_at' del registro y convertirlo a objeto Carbon
                    $createdAt = Carbon::parse($operatorPost->created_at);

                    // Si la fecha de 'created_at' es anterior a la fecha actual, cerramos y duplicamos
                    if ($createdAt->toDateString() < $currentDate->toDateString()) {
                        // Si es antes de la fecha actual, se cierra el registro con la hora 23:59:59
                        $operatorPost->update([
                            'finish_at' => $createdAt->copy()->setTime(23, 59, 59),
                        ]);

                        // Duplicamos el registro con la fecha del siguiente día a las 00:00:01
                        $newData = $operatorPost->toArray();
                        unset($newData['id']); // Eliminar el campo 'id' para crear un nuevo registro
                        $newData['count'] = 0; // Reiniciar el contador a 0 para el nuevo registro
                        $newData['finish_at'] = null; // Dejar finish_at como null para el nuevo registro
                        $newData['created_at'] = $createdAt->copy()->addDay()->setTime(0, 0, 1); // Fecha de mañana a las 00:00:01

                        // Crear el nuevo registro duplicado
                        OperatorPost::create($newData);

                        // Mostrar mensaje en la consola
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]Se ha finalizado el operador post ID {$operatorPost->id} y duplicado para el siguiente día.");
                    } else {
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]El operador post ID {$operatorPost->id} No necesita ser finalizado porque ya está en la fecha correcta.");
                    }

                    // Si el 'created_at' es de hoy y despues de 23:59:00, lo cerramos a las 23:59:59
                    if ($createdAt->isToday() && $createdAt->format('H:i:s') >= '23:59:00' && $createdAt->format('H:i:s') < '23:59:59') {
                        // Cerrar el registro a las 23:59:59
                        $operatorPost->update([
                            'finish_at' => $createdAt->copy()->setTime(23, 59, 59),
                        ]);

                        // Duplicamos el registro para el siguiente día a las 00:00:01
                        $newData = $operatorPost->toArray();
                        unset($newData['id']); // Eliminar el campo 'id' para crear un nuevo registro
                        $newData['count'] = 0; // Reiniciar el contador a 0 para el nuevo registro
                        $newData['finish_at'] = null; // Dejar finish_at como null para el nuevo registro
                        $newData['created_at'] = $createdAt->copy()->addDay()->setTime(0, 0, 1); // Fecha de mañana a las 00:00:01

                        // Crear el nuevo registro duplicado
                        OperatorPost::create($newData);

                        // Mostrar mensaje en la consola
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]Se ha finalizado el operador post ID {$operatorPost->id} y duplicado para el siguiente día.");
                    }

                    // Si la fecha de 'created_at' es del día siguiente (mañana), la ignoramos
                    if ($createdAt->isTomorrow()) {
                        $this->info("[" . Carbon::now()->toDateTimeString() . "]Se ha ignorado el operador post ID {$operatorPost->id} porque su fecha es de mañana.");
                    }
                }

                // Confirmación en consola de que el ciclo ha terminado sin errores
                $this->info('Ciclo de finalización de registros de operadores completado.');
            } catch (\Exception $e) {
                // Si ocurre un error, se captura y se muestra en consola
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al procesar los registros de operadores: " . $e->getMessage());
            }

            // Dormir 20 segundos antes de ejecutar el siguiente ciclo
            usleep(20000000); // 20 segundos (en microsegundos)
        }
    }
}
