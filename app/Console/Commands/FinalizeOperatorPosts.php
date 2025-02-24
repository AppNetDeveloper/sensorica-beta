<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperatorPost;
use Carbon\Carbon;
use Log;

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
        // Bucle infinito para que se ejecute continuamente
        while (true) {
            try {
                // Obtener la fecha y hora actual
                $currentDate = Carbon::now();

                // Buscar todos los registros de OperatorPost que no tengan finish_at
                $operatorPosts = OperatorPost::whereNull('finish_at')->get();

                foreach ($operatorPosts as $operatorPost) {
                    // Obtiene el created_at del registro
                    $createdAt = Carbon::parse($operatorPost->created_at);

                    // Si el created_at es del mismo día, finalizarlo con 23:59:59
                    if ($createdAt->isToday()) {
                        // Finalizar el registro con la hora 23:59:59
                        $operatorPost->update([
                            'finish_at' => $createdAt->copy()->setTime(23, 59, 59),
                        ]);

                        // Duplicar la entrada para el siguiente día
                        $newData = $operatorPost->toArray();
                        unset($newData['id']); // Aseguramos que se cree un nuevo ID.
                        $newData['finish_at'] = null;
                        $newData['created_at'] = $createdAt->copy()->addDay()->setTime(0, 0, 1);

                        // Crear el nuevo registro duplicado
                        OperatorPost::create($newData);

                        // Log para la operación
                        Log::info("Se ha finalizado el operador post ID {$operatorPost->id} y duplicado para el siguiente día.");
                    }
                }

                // Si es después de medianoche, duplicamos todos los registros con la fecha del siguiente día
                if ($currentDate->isAfter($currentDate->copy()->endOfDay())) {
                    $this->duplicateOperatorPostsForNewDay();
                }

                // Log para indicar que el ciclo se ha completado exitosamente
                Log::info('Ciclo de finalización de registros de operadores completado.');

            } catch (\Exception $e) {
                // Log del error para poder manejar cualquier fallo
                Log::error("Error en el ciclo de finalización de registros: " . $e->getMessage());
            }

            // Dormir por 60 segundos antes de volver a comprobar
            sleep(60);
        }
    }

    /**
     * Duplicar los registros de OperatorPost para el siguiente día.
     */
    private function duplicateOperatorPostsForNewDay()
    {
        $operatorPosts = OperatorPost::whereNull('finish_at')->get();

        foreach ($operatorPosts as $operatorPost) {
            $newData = $operatorPost->toArray();
            unset($newData['id']); // Aseguramos que se cree un nuevo ID.
            $newData['finish_at'] = null;
            $newData['created_at'] = Carbon::today()->addDay()->setTime(0, 0, 1);

            // Crear el nuevo registro duplicado
            OperatorPost::create($newData);

            // Log para la duplicación
            Log::info("Se ha duplicado el operador post ID {$operatorPost->id} para el siguiente día.");
        }
    }
}
