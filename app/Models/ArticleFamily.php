<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleFamily extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * Obtener los artículos asociados a esta familia de artículos
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}