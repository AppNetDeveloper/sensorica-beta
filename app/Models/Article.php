<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'article_family_id'];

    /**
     * Obtener la familia de artículos a la que pertenece este artículo
     */
    public function articleFamily()
    {
        return $this->belongsTo(ArticleFamily::class);
    }

    /**
     * Las líneas de producción asociadas a este artículo
     */
    public function productionLines()
    {
        return $this->belongsToMany(ProductionLine::class, 'production_line_article')
            ->withPivot('order')
            ->orderBy('production_line_article.order');
    }
}