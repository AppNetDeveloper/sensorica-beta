<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'user_id',
        'type',
        'title',
        'description',
        'payload',
        'event_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'event_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
