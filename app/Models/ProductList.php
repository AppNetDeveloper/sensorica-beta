<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductList extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'optimal_production_time',
    ];

    // Relationship with Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
