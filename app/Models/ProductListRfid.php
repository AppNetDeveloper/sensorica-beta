<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductListRfid extends Model
{
    use HasFactory;

    protected $table = 'product_list_rfid';

    protected $fillable = [
        'product_list_id',
        'rfid_reading_id',
    ];

    /**
     * Relación con ProductList.
     */
    public function productList()
    {
        return $this->belongsTo(ProductList::class);
    }

    /**
     * Relación con RfidReading.
     */
    public function rfidReading()
    {
        return $this->belongsTo(RfidReading::class);
    }

    /**
     * Boot method to add logic before creating a new record.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Actualizar updated_at del registro anterior con el mismo product_list_id
            $existingProduct = self::where('product_list_id', $model->product_list_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingProduct) {
                $existingProduct->updated_at = now(); // Actualizar updated_at manualmente
                $existingProduct->save();
            }

            // Actualizar updated_at del registro anterior con el mismo rfid_reading_id
            $existingRfid = self::where('rfid_reading_id', $model->rfid_reading_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingRfid) {
                $existingRfid->updated_at = now(); // Actualizar updated_at manualmente
                $existingRfid->save();
            }

            // Prevenir que el registro actual tenga un valor en updated_at
            $model->updated_at = null; // Establecer null para mostrar que está "en uso"
        });
    }
}
