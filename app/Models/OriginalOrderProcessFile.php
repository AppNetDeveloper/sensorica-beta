<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OriginalOrderProcessFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_order_process_id',
        'token',
        'original_name',
        'mime_type',
        'size',
        'extension',
        'disk',
        'path',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(OriginalOrderProcess::class, 'original_order_process_id');
    }

    public function getPublicUrlAttribute(): string
    {
        // Served through public/storage symlink
        return asset('storage/' . ltrim($this->path, '/'));
    }
}
