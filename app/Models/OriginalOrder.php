<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OriginalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'client_number',
        'order_details',
        'processed',
        'finished_at',
        'delivery_date',
        'in_stock',
    ];

    protected $casts = [
        'order_details' => 'json',
        'processed' => 'boolean',
        'finished_at' => 'datetime',
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'finished_at',
        'delivery_date'
    ];

    /**
     * IMPORTANTE: Se han eliminado los eventos como updated() para evitar bucles infinitos.
     * La lógica ahora vive en los dos métodos de abajo, que son llamados por el controlador.
     */

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'original_order_processes')
                    ->using(OriginalOrderProcess::class)
                    ->withPivot(['id', 'time', 'created', 'finished', 'finished_at', 'grupo_numero']) // <-- ¡AÑADIDO!
                    ->withTimestamps();
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function articles()
    {
        return $this->hasManyThrough(
            OriginalOrderArticle::class,
            OriginalOrderProcess::class,
            'original_order_id',
            'original_order_process_id',
            'id',
            'id'
        );
    }
    
    public function orderProcesses()
    {
        return $this->hasMany(OriginalOrderProcess::class, 'original_order_id');
    }
    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'original_order_id');
    }

    public function originalOrderProcesses()
    {
        return $this->hasMany(OriginalOrderProcess::class);
    }
    
    /**
     * Comprueba si todos los procesos de esta orden están finalizados.
     * Esta es la lógica correcta para contar los procesos.
     *
     * @return bool
     */
    /**
     * Comprueba si todos los procesos de esta orden están finalizados.
     */
    public function allProcessesFinished(): bool
    {
        $totalProcesses = $this->orderProcesses()->count();
        if ($totalProcesses === 0) return false;
        
        $finishedProcesses = $this->orderProcesses()->where('finished', true)->count();
        return $finishedProcesses === $totalProcesses;
    }
    
    /**
     * Actualiza el estado de finalización y se guarda a sí mismo de forma segura.
     * Este método es público para ser llamado desde el evento del modelo pivot.
     */
    public function updateFinishedStatus(): bool
    {
        $allFinished = $this->allProcessesFinished();
        $changed = false;
        
        if ($allFinished && is_null($this->finished_at)) {
            $this->finished_at = now();
            $changed = true;
            Log::info("Orden {$this->id}: Todos los procesos finalizados. Marcando como terminada.");
        } 
        elseif (!$allFinished && !is_null($this->finished_at)) {
            $this->finished_at = null;
            $changed = true;
            Log::info("Orden {$this->id}: Un proceso fue revertido. Eliminando fecha de finalización.");
        }
        
        if ($changed) {
            // ¡ESTA ES LA CLAVE!
            // saveQuietly() guarda el modelo SIN disparar ningún evento (como 'updated').
            // Esto rompe el bucle infinito.
            return $this->saveQuietly(); 
        }
        
        return false;
    }
}