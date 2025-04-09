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

    public function operatorPosts()
    {
        return $this->hasMany(OperatorPost::class, 'operator_id', 'id');
    }
    public function shiftHistories()
    {
        return $this->hasMany(ShiftHistory::class, 'operator_id');
    }
}
