<?php

namespace App\Http\Controllers;

use App\Models\ArticleFamily;
use Illuminate\Http\Request;

class ArticleFamilyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:article-family-show|article-family-create|article-family-edit|article-family-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:article-family-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:article-family-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:article-family-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the article families.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $articleFamilies = ArticleFamily::orderBy('name')->get();
        return view('article-families.index', compact('articleFamilies'));
    }

    /**
     * Show the form for creating a new article family.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('article-families.create');
    }

    /**
     * Store a newly created article family in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:article_families,name',
            'description' => 'nullable|string',
        ]);

        ArticleFamily::create($validated);

        return redirect()->route('article-families.index')
            ->with('success', 'Familia de artículos creada correctamente.');
    }

    /**
     * Display the specified article family.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\View\View
     */
    public function show(ArticleFamily $articleFamily)
    {
        return view('article-families.show', compact('articleFamily'));
    }

    /**
     * Show the form for editing the specified article family.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\View\View
     */
    public function edit(ArticleFamily $articleFamily)
    {
        return view('article-families.edit', compact('articleFamily'));
    }

    /**
     * Update the specified article family in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ArticleFamily $articleFamily)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:article_families,name,' . $articleFamily->id,
            'description' => 'nullable|string',
        ]);

        $articleFamily->update($validated);

        return redirect()->route('article-families.index')
            ->with('success', 'Familia de artículos actualizada correctamente.');
    }

    /**
     * Remove the specified article family from storage.
     *
     * @param  \App\Models\ArticleFamily  $articleFamily
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ArticleFamily $articleFamily)
    {
        try {
            $articleFamily->delete();
            return redirect()->route('article-families.index')
                ->with('success', 'Familia de artículos eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'No se pudo eliminar la familia de artículos. Asegúrese de que no esté siendo utilizada.');
        }
    }
}