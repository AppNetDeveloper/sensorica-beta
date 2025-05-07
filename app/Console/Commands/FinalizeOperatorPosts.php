<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperatorPost;
use App\Models\ShiftHistory;
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
    protected $description = 'Cierra y gestiona los registros de operadores según el inicio y fin de turno.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        while (true) {
            try {
                $now = Carbon::now();

                // Obtener el último inicio de turno registrado
                $lastShift = ShiftHistory::latest('created_at')->first();

                // Si no hay turno registrado, esperar y volver a intentarlo
                if (!$lastShift) {
                    $this->info("[{$now->toDateTimeString()}] No se ha encontrado un inicio de turno válido (type=shift, action=start). Esperando...");
                    usleep(20000000);
                    continue;
                }
                //si el ultimo turno no es type=shift o action=start, esperar y volver a intentarlo
                if ($lastShift->type != 'shift' || $lastShift->action != 'start') {
                    $this->info("[{$now->toDateTimeString()}] El último inicio de turno registrado no es válido (type=shift, action=start). Esperando...");
                    usleep(20000000);
                    continue;
                }

                // Convertir inicio de turno a Carbon
                $todayShiftStart = Carbon::parse($lastShift->created_at);

                // Obtener el último fin de turno registrado
                $lastShiftEnd = ShiftHistory::where('type', 'shift')
                    ->where('action', 'end')
                    ->latest('created_at')
                    ->first();

                // Buscar los posts no finalizados creados antes del inicio de turno actual
                $operatorPosts = OperatorPost::whereNull('finish_at')
                    ->where('created_at', '<', $todayShiftStart)
                    ->get();

                foreach ($operatorPosts as $post) {

                    // Determinar finish_at usando el último fin de turno o, si no existe, el inicio de turno actual
                    $finishAt = $lastShiftEnd
                        ? Carbon::parse($lastShiftEnd->created_at)
                        : $todayShiftStart;

                    // Actualizar finish_at del registro original
                    $post->update(['finish_at' => $finishAt]);

                    // Preparar y crear nuevo registro duplicado para el turno actual
                    $data = $post->toArray();
                    unset($data['id']);
                    $data['count']     = 0;
                    $data['finish_at'] = null;
                    $data['created_at'] = $todayShiftStart;

                    OperatorPost::create($data);

                    $this->info("[{$now->toDateTimeString()}] Post ID {$post->id} cerrado a {$finishAt} y duplicado con created_at={$todayShiftStart}.");
                }

                $this->info("[{$now->toDateTimeString()}] Ciclo completado sin errores.");

            } catch (\Exception $e) {
                $this->error("[{$now->toDateTimeString()}] Error: {$e->getMessage()}");
            }

            usleep(20000000);
        }
    }
}
