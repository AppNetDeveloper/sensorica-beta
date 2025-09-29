<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\CustomerClient;
use App\Models\RouteName;

class OriginalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'customer_client_id',
        'route_name_id',
        'client_number',
        'order_details',
        'processed',
        'finished_at', //fecha de pedido finalizado cuando el pedido se completa
        'delivery_date', //fecha de de entrega pedido programada por erp
        'estimated_delivery_date', //fecha de entrega pedido estimada
        'actual_delivery_date', //fecha de entrega pedido real
        'delivery_signature', //firma digital del cliente en base64
        'delivery_photos', //array de rutas de fotos de entrega
        'delivery_notes', //notas del transportista
        'in_stock',
        'fecha_pedido_erp', //fecha de pedido en ERP
    ];

    protected $casts = [
        'order_details' => 'json',
        'processed' => 'boolean',
        'finished_at' => 'datetime',
        'delivery_date' => 'date',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'delivery_photos' => 'json',
        'fecha_pedido_erp' => 'date',
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'finished_at',
        'delivery_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'fecha_pedido_erp'
    ];

    /**
     * IMPORTANTE: Se han eliminado los eventos como updated() para evitar bucles infinitos.
     * La lógica ahora vive en los dos métodos de abajo, que son llamados por el controlador.
     */

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'original_order_processes')
                    ->using(OriginalOrderProcess::class)
                    ->withPivot(['id', 'time', 'created', 'finished', 'finished_at', 'grupo_numero', 'box', 'units_box', 'number_of_pallets', 'in_stock']) // incluir campos extra del pivot
                    ->withTimestamps();
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerClient()
    {
        return $this->belongsTo(CustomerClient::class);
    }

    public function routeName()
    {
        return $this->belongsTo(RouteName::class);
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

    /**
     * Relación con confirmaciones de Calidad (QC) para esta orden original.
     */
    public function qcConfirmations()
    {
        return $this->hasMany(QcConfirmation::class, 'original_order_id');
    }

    /**
     * Indica si existen confirmaciones de QC asociadas a esta orden.
     */
    public function hasQcConfirmations(): bool
    {
        return $this->qcConfirmations()->exists();
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
     * Actualiza el estado de stock de la orden basado en sus procesos
     * - Si algún proceso tiene in_stock = 0, la orden tendrá in_stock = 0
     * - Si todos los procesos tienen in_stock = 1, la orden tendrá in_stock = 1
     * - Si no hay procesos, se mantiene el estado actual
     * 
     * @return bool True si el estado cambió, False en caso contrario
     */
    public function updateInStockStatus(): bool
    {
        // Si no hay procesos, no hacemos nada
        if ($this->orderProcesses()->count() === 0) {
            return false;
        }
        
        // Verificar si hay al menos un proceso sin stock
        $hasOutOfStock = $this->orderProcesses()->where('in_stock', 0)->exists();
        $newInStockValue = $hasOutOfStock ? 0 : 1;
        
        // Solo actualizamos si el valor cambió
        if ($this->in_stock !== $newInStockValue) {
            $this->in_stock = $newInStockValue;
            $this->save();
            return true;
        }
        
        return false;
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