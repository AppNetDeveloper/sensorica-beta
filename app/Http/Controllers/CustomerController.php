<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log; // Agrega esta línea para usar Log


class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function getCustomers(Request $request)
    {
        // Construye la consulta base para los clientes
        $query = Customer::query();

        // Usa DataTables para procesar la consulta y añadir la columna de acción
        return DataTables::of($query)
            ->addColumn('action', function ($customer) {
                // URLs para las diferentes acciones
                $editUrl = route('customers.edit', $customer->id);
                // *** CAMBIO: Corregido el nombre del parámetro de 'customer' a 'customer_id' ***
                $productionLinesUrl = route('productionlines.index', ['customer_id' => $customer->id]); // Asegúrate que la ruta acepte el parámetro así
                $deleteUrl = route('customers.destroy', $customer->id);
                $csrfToken = csrf_token();
                // Genera URLs seguras si tu aplicación corre sobre HTTPS
                $liveViewUrl = secure_url('/modbuses/liststats/weight?token=' . $customer->token_zerotier); // Usa token_zerotier si ese es el campo correcto
                $liveViewUrlProd = secure_url('/productionlines/liststats?token=' . $customer->token_zerotier); // Usa token_zerotier si ese es el campo correcto

                // Construye el HTML para los botones con iconos (Font Awesome)
                // Añade un pequeño margen a la derecha del icono (me-1)
                // Añade tooltips con el atributo 'title'

                $editButton = "<a href='{$editUrl}' class='btn btn-sm btn-info me-1' title='" . __('Edit') . "'><i class='fas fa-edit me-1'></i>" . __('Edit') . "</a>";

                $linesButton = "<a href='{$productionLinesUrl}' class='btn btn-sm btn-secondary me-1' title='" . __('Production Lines') . "'><i class='fas fa-sitemap me-1'></i>" . __('Production Lines') . "</a>";

                $deleteForm = "<form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit='return confirm(\"" . __('Are you sure?') . "\");'>
                                <input type='hidden' name='_token' value='{$csrfToken}'>
                                <input type='hidden' name='_method' value='DELETE'>
                                <button type='submit' class='btn btn-sm btn-danger me-1' title='" . __('Delete') . "'><i class='fas fa-trash me-1'></i>" . __('Delete') . "</button>
                               </form>";

                $weightStatsButton = "<a href='{$liveViewUrl}' class='btn btn-sm btn-success me-1' title='" . __('Weight Stats') . "' target='_blank'><i class='fas fa-weight-hanging me-1'></i>" . __('Weight Stats') . "</a>"; // target='_blank' para abrir en nueva pestaña

                $prodStatsButton = "<a href='{$liveViewUrlProd}' class='btn btn-sm btn-warning me-1' title='" . __('Production Stats') . "' target='_blank'><i class='fas fa-chart-line me-1'></i>" . __('Production Stats') . "</a>"; // target='_blank' para abrir en nueva pestaña


                // Concatena todos los botones
                return  $linesButton  . $weightStatsButton . $prodStatsButton. $editButton . $deleteForm;
            })
            // Indica a DataTables que la columna 'action' contiene HTML y no debe ser escapada
            ->rawColumns(['action'])
            // Genera la respuesta JSON para DataTables
            ->make(true);
    }

     

    public function testCustomers()
    {
        $customers = Customer::all();
        dd($customers); 
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token_zerotier' => 'required|string|max:255',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update($request->all());

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'token_zerotier' => 'required|string|max:255',
        ]);

        // Generar un token único
        $token = bin2hex(random_bytes(16));

        Customer::create([
            'name' => $request->name,
            'token_zerotier' => $request->token_zerotier,
            'token' => $token, // Asignar token único
        ]);

        return redirect()->route('customers.index')->with('success', 'Cliente creado con éxito.');
    }

}
