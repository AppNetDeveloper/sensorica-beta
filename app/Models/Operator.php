<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'password',
        'email',
        'phone',
        'count_shift',
        'count_order',
    ];

    // Relationship with Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
