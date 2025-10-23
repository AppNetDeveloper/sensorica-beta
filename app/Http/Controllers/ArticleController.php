<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleFamily;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:article-show|article-create|article-edit|article-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:article-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:article-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:article-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the articles for a specific family.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\View\View
     */
    public function index(ArticleFamily $articleFamily)
    {
        $articles = $articleFamily->articles()->with('articleFamily')->orderBy('name')->get();
        return view('articles.index', compact('articles', 'articleFamily'));
    }

    /**
     * Show the form for creating a new article.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\View\View
     */
    public function create(ArticleFamily $articleFamily)
    {
        $articleFamilies = ArticleFamily::orderBy('name')->get();
        return view('articles.create', compact('articleFamilies', 'articleFamily'));
    }

    /**
     * Store a newly created article in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, ArticleFamily $articleFamily)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:articles,name',
            'description' => 'nullable|string',
        ]);

        $validated['article_family_id'] = $articleFamily->id;

        Article::create($validated);

        return redirect()->route('article-families.articles.index', $articleFamily)
            ->with('success', 'Artículo creado correctamente.');
    }

    /**
     * Display the specified article.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @param  \App\Models\Article  $article
     * @return \Illuminate\View\View
     */
    public function show(ArticleFamily $articleFamily, Article $article)
    {
        $article->load('articleFamily');
        return view('articles.show', compact('article', 'articleFamily'));
    }

    /**
     * Show the form for editing the specified article.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @param  \App\Models\Article  $article
     * @return \Illuminate\View\View
     */
    public function edit(ArticleFamily $articleFamily, Article $article)
    {
        $articleFamilies = ArticleFamily::orderBy('name')->get();
        return view('articles.edit', compact('article', 'articleFamilies', 'articleFamily'));
    }

    /**
     * Update the specified article in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ArticleFamily $articleFamily, Article $article)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:articles,name,' . $article->id,
            'description' => 'nullable|string',
            'article_family_id' => 'required|exists:article_families,id',
        ]);

        $article->update($validated);

        return redirect()->route('article-families.articles.index', $articleFamily)
            ->with('success', 'Artículo actualizado correctamente.');
    }

    /**
     * Remove the specified article from storage.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ArticleFamily $articleFamily, Article $article)
    {
        try {
            $article->delete();
            return redirect()->route('article-families.articles.index', $articleFamily)
                ->with('success', 'Artículo eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'No se pudo eliminar el artículo. Asegúrese de que no esté siendo utilizado.');
        }
    }
}