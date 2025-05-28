<?php

namespace App\Http\Controllers;

use App\Models\IaPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IaPromptAdminController extends Controller
{
    /**
     * Crea una nueva instancia del controlador.
     * Aplica el middleware de rol para restringir el acceso.
     */
    public function __construct()
    {
        // Esto asegura que solo los usuarios con el rol 'admin' puedan acceder
        // a cualquier método de este controlador.
        // Asegúrate de que el rol 'admin' exista en tu base de datos (tabla 'roles' de Spatie).
        $this->middleware('role:admin');

        // Si quisieras ser más específico y permitir diferentes roles o permisos por método:
        // $this->middleware('role:admin')->only(['index', 'edit', 'update']);
        // $this->middleware('permission:edit prompts')->only(['edit', 'update']);
    }

    /**
     * Muestra una lista de los prompts de IA.
     */
    public function index(): View
    {
        $prompts = IaPrompt::orderBy('name')->get();
        return view('ia_prompts.index', compact('prompts'));
    }

    /**
     * Muestra el formulario para editar un prompt específico.
     */
    public function edit(IaPrompt $iaPrompt): View
    {
        return view('ia_prompts.edit', compact('iaPrompt'));
    }

    /**
     * Actualiza el prompt especificado en la base de datos.
     */
    public function update(Request $request, IaPrompt $iaPrompt): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'model_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $validatedData['is_active'] = $request->has('is_active');

        $iaPrompt->update($validatedData);

        return redirect()->route('ia_prompts.index') // Asume que esta ruta no tiene el prefijo 'admin.'
                         ->with('success', 'Prompt actualizado correctamente.');
    }
}