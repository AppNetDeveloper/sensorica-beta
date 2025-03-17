<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidBlocked extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rfid_blocked';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['epc'];
}
