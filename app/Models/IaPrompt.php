<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IaPrompt extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    // Laravel intentará usar 'ia_prompts' por defecto si el modelo se llama IaPrompt,
    // pero es bueno ser explícito si el nombre no sigue exactamente la convención (singular vs plural).
    // En este caso, 'ia_prompts' es el plural de 'ia_prompt', por lo que Laravel debería encontrarlo.
    // Si no, descomenta y ajusta:
    // protected $table = 'ia_prompts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'content',
        'model_name',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean', // Para que 'is_active' se maneje como un booleano
    ];

    // No necesitas definir $timestamps = true; si tu tabla tiene
    // las columnas created_at y updated_at, Eloquent las maneja por defecto.
}