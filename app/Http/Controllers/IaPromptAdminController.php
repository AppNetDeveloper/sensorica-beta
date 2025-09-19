<?php

namespace App\Http\Controllers;

use App\Models\IaPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;

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

    /**
     * Ejecuta el comando Artisan para regenerar las plantillas por cada descripción única.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $modelName = $request->input('model_name');
        $activate = $request->boolean('activate', true);
        $update = $request->boolean('update', true);

        $params = ['--activate' => $activate ? '1' : '0', '--update' => $update ? '1' : '0'];
        if (!empty($modelName)) {
            $params['--model_name'] = $modelName;
        }

        Artisan::call('ia:ensure-process-group-prompts', $params);
        $output = Artisan::output();

        return redirect()
            ->route('ia_prompts.index')
            ->with('success', 'Plantillas regeneradas correctamente.')
            ->with('artisan_output', $output);
    }
}