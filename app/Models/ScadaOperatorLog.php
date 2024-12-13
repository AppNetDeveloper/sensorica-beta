<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaOperatorLog extends Model
{
    use HasFactory;

    protected $fillable = ['operator_id', 'scada_id'];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function scada()
    {
        return $this->belongsTo(Scada::class);
    }
}
