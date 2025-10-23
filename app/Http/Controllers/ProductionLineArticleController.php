<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\Article;
use App\Models\ArticleFamily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionLineArticleController extends Controller
{
    /**
     * Middleware para verificar permisos
     */
    public function __construct()
    {
        $this->middleware('permission:productionline-article-view', ['only' => ['index', 'show']]);
        $this->middleware('permission:productionline-article-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:productionline-article-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:productionline-article-delete', ['only' => ['destroy', 'bulkDelete']]);
    }

    /**
     * Display a listing of the articles for a production line.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ProductionLine $productionLine)
    {
        $query = $productionLine->articles();

        // Filtrar por familia de artículos si se proporciona
        if ($request->has('article_family_id') && $request->article_family_id) {
            $query->whereHas('articleFamily', function($q) use ($request) {
                $q->where('id', $request->article_family_id);
            });
        }

        $articles = $query->orderBy('production_line_article.order')->get();

        $articleFamilies = ArticleFamily::all();

        return view('productionlines.articles.index', compact('productionLine', 'articles', 'articleFamilies'));
    }

    /**
     * Show the form for creating a new article association.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function create(ProductionLine $productionLine)
    {
        // Obtener artículos que aún no están asociados a esta línea de producción
        $availableArticles = Article::whereNotIn('id', function($query) use ($productionLine) {
            $query->select('article_id')
                  ->from('production_line_article')
                  ->where('production_line_id', $productionLine->id);
        })->with('articleFamily')->orderBy('name')->get();

        // Obtener familias de artículos para opción de selección masiva
        $articleFamilies = ArticleFamily::with(['articles' => function($query) use ($productionLine) {
            $query->whereNotIn('id', function($subQuery) use ($productionLine) {
                $subQuery->select('article_id')
                         ->from('production_line_article')
                         ->where('production_line_id', $productionLine->id);
            });
        }])->get();

        return view('productionlines.articles.create', compact('productionLine', 'availableArticles', 'articleFamilies'));
    }

    /**
     * Store a newly created article association in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ProductionLine $productionLine)
    {
        $validated = $request->validate([
            'article_id' => 'nullable|exists:articles,id',
            'article_family_id' => 'nullable|exists:article_families,id',
            'order' => 'required|integer|min:1'
        ]);

        try {
            $articlesToAttach = [];

            if ($validated['article_id']) {
                // Asociar artículo individual
                $articlesToAttach[] = $validated['article_id'];
            } elseif ($validated['article_family_id']) {
                // Asociar todos los artículos de la familia
                $family = ArticleFamily::findOrFail($validated['article_family_id']);
                $articlesToAttach = $family->articles->pluck('id')->toArray();
            } else {
                return redirect()->back()
                    ->with('error', __('Debe seleccionar un artículo o una familia de artículos.'))
                    ->withInput();
            }

            // Verificar si ya existe una relación con el mismo orden
            $existingOrder = DB::table('production_line_article')
                ->where('production_line_id', $productionLine->id)
                ->where('order', $validated['order'])
                ->exists();

            if ($existingOrder) {
                return redirect()->back()
                    ->with('error', __('Ya existe un artículo con este orden en la línea de producción.'))
                    ->withInput();
            }

            // Verificar si algún artículo ya está asociado
            $existingArticles = DB::table('production_line_article')
                ->where('production_line_id', $productionLine->id)
                ->whereIn('article_id', $articlesToAttach)
                ->pluck('article_id')
                ->toArray();

            if (!empty($existingArticles)) {
                $existingNames = Article::whereIn('id', $existingArticles)->pluck('name')->toArray();
                return redirect()->back()
                    ->with('error', __('Los siguientes artículos ya están asociados: ') . implode(', ', $existingNames))
                    ->withInput();
            }

            // Asociar los artículos con el orden especificado
            foreach ($articlesToAttach as $articleId) {
                $productionLine->articles()->attach($articleId, [
                    'order' => $validated['order']
                ]);
            }

            $message = count($articlesToAttach) > 1 ?
                __('Artículos de la familia asociados correctamente.') :
                __('Artículo asociado correctamente.');

            return redirect()->route('productionlines.articles.index', $productionLine->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Error al asociar el artículo: ') . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified article association.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function show(ProductionLine $productionLine, Article $article)
    {
        $pivot = DB::table('production_line_article')
            ->where('production_line_id', $productionLine->id)
            ->where('article_id', $article->id)
            ->first();

        if (!$pivot) {
            abort(404);
        }

        return view('productionlines.articles.show', compact('productionLine', 'article', 'pivot'));
    }

    /**
     * Show the form for editing the specified article association.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductionLine $productionLine, Article $article)
    {
        $pivot = DB::table('production_line_article')
            ->where('production_line_id', $productionLine->id)
            ->where('article_id', $article->id)
            ->first();

        if (!$pivot) {
            abort(404);
        }

        return view('productionlines.articles.edit', compact('productionLine', 'article', 'pivot'));
    }

    /**
     * Update the specified article association in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductionLine $productionLine, Article $article)
    {
        $validated = $request->validate([
            'order' => 'required|integer|min:1'
        ]);

        try {
            // Verificar si ya existe otra relación con el mismo orden
            $existing = DB::table('production_line_article')
                ->where('production_line_id', $productionLine->id)
                ->where('article_id', '!=', $article->id)
                ->where('order', $validated['order'])
                ->exists();

            if ($existing) {
                return redirect()->back()
                    ->with('error', __('Ya existe otro artículo con este orden en la línea de producción.'))
                    ->withInput();
            }

            // Actualizar el orden del artículo
            $productionLine->articles()->updateExistingPivot($article->id, [
                'order' => $validated['order']
            ]);

            return redirect()->route('productionlines.articles.index', $productionLine->id)
                ->with('success', __('Orden del artículo actualizado correctamente.'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Error al actualizar el orden del artículo: ') . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified article association from storage.
     *
     * @param  \App\Models\ProductionLine  $productionLine
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductionLine $productionLine, Article $article)
    {
        try {
            // Eliminar la relación
            $productionLine->articles()->detach($article->id);

            return redirect()->route('productionlines.articles.index', $productionLine->id)
                ->with('success', __('Artículo desasociado correctamente.'));

        } catch (\Exception $e) {
            return redirect()->route('productionlines.articles.index', $productionLine->id)
                ->with('error', __('Error al desasociar el artículo: ') . $e->getMessage());
        }
    }

    /**
     * Remove multiple article associations from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductionLine  $productionLine
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request, ProductionLine $productionLine)
    {
        $request->validate([
            'article_ids' => 'required|array',
            'article_ids.*' => 'exists:articles,id'
        ]);

        try {
            $articleIds = $request->article_ids;
            $deletedCount = 0;

            foreach ($articleIds as $articleId) {
                // Verificar que el artículo esté asociado a esta línea de producción
                $exists = DB::table('production_line_article')
                    ->where('production_line_id', $productionLine->id)
                    ->where('article_id', $articleId)
                    ->exists();

                if ($exists) {
                    $productionLine->articles()->detach($articleId);
                    $deletedCount++;
                }
            }

            return redirect()->route('productionlines.articles.index', $productionLine->id)
                ->with('success', __('Artículos desasociados correctamente: ') . $deletedCount);

        } catch (\Exception $e) {
            return redirect()->route('productionlines.articles.index', $productionLine->id)
                ->with('error', __('Error al desasociar los artículos: ') . $e->getMessage());
        }
    }
}