<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Process; // Añadido para la relación belongsToMany

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
        'finished_at' => 'datetime'
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'finished_at',
        'delivery_date'
    ];

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'original_order_processes')
                    ->withPivot('created', 'finished_at')
                    ->withTimestamps()
                    ->using(OriginalOrderProcess::class);
    }
    
    /**
     * Get the customer that owns the original order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Check if all processes are finished for this order
     *
     * @return bool
     */
    public function allProcessesFinished()
    {
        // An order must have at least one process to be considered "finishable" based on its processes.
        // We use processes() to initiate a query, ensuring fresh data.
        if ($this->processes()->count() === 0) {
            return false; 
        }
        
        // Count how many of the associated processes DO NOT have a finished_at date in the pivot table.
        // Again, using processes() ensures a fresh query.
        $unfinishedProcessesCount = $this->processes()
                                         ->wherePivotNull('finished_at')
                                         ->count();
                                         
        // If the count of unfinished processes is zero, then all processes are finished.
        return $unfinishedProcessesCount === 0;
    }
    
    /**
     * Update the finished_at timestamp if all processes are finished
     *
     * @return bool
     */
    public function updateFinishedStatus()
    {
        if ($this->allProcessesFinished() && !$this->finished_at) {
            $this->finished_at = now();
            return $this->save();
        }
        
        // If not all processes are finished but finished_at is set, clear it
        if (!$this->allProcessesFinished() && $this->finished_at) {
            $this->finished_at = null;
            return $this->save();
        }
        
        return false;
    }
}
