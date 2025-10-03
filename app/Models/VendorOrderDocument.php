<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorOrderDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_order_id',
        'type',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    public function order()
    {
        return $this->belongsTo(VendorOrder::class, 'vendor_order_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
