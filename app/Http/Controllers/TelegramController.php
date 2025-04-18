<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    private $baseUrl = 'http://localhost:3006';

    public function index()
    {
        $response = Http::get("http://localhost:3006/active-sessions");
    
        $isConnected = false;
    
        if ($response->successful()) {
            $data = $response->json();
            foreach ($data['sessions'] as $session) {
                if ($session['userId'] == '1' && $session['isConnected']) {
                    $isConnected = true;
                    break;
                }
            }
        }
    
        return view('telegram.index', compact('isConnected')); // Se cambia a 'telegram.index'
    }
    

    public function requestCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);
    
        try {
            $response = Http::post("http://localhost:3006/request-code/1", [
                'phone' => $request->phone,
            ]);
    
            if (!$response->successful()) {
                throw new \Exception("Error en solicitud de código: " . $response->body());
            }
    
            session(['phone' => $request->phone]); // Guardamos el teléfono en la sesión
    
            return redirect()->route('telegram.index')->with('status', 'Código de verificación enviado.');
        } catch (\Exception $e) {
            return back()->with('error', "No se pudo solicitar el código: " . $e->getMessage());
        }
    }
    
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => 'nullable|string',
        ]);
    
        try {
            $response = Http::post("http://localhost:3006/verify-code/1", [
                'code' => $request->code,
                'password' => $request->password ?? '',
            ]);
    
            if (!$response->successful()) {
                throw new \Exception("Error en verificación: " . $response->body());
            }
    
            session()->forget('phone'); // Eliminamos el teléfono de la sesión
    
            return redirect()->route('telegram.index')->with('status', 'Sesión iniciada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', "Código incorrecto o sesión fallida.");
        }
    }
    

    public function logout()
    {
        
        $response = Http::post("{$this->baseUrl}/logout/1");

        if ($response->successful()) {
            session()->forget('phone'); // Eliminamos el teléfono de la sesión
            return response()->with('status', 'Sesión cerrada correctamente.');
        }
        session()->forget('phone');
        return back()->with('error', 'Error al cerrar sesión.');
    }
    

    public function sendMessage(Request $request)
    {
        $request->validate([
            'peer' => 'required|string',
            'message' => 'required|string',
        ]);
        
        $response = Http::post("{$this->baseUrl}/send-message/1/+{$request->peer}/{$request->message}");

        if ($response->successful()) {
            return back()->with('status', 'Mensaje enviado.');
        }

        return back()->with('error', 'Error al enviar el mensaje.');
    }

    public function status()
    {
        try {
            $response = Http::get("http://localhost:3006/active-sessions");

            $isConnected = false;

            if ($response->successful()) {
                $data = $response->json();
                foreach ($data['sessions'] as $session) {
                    if ($session['userId'] == '1' && $session['isConnected']) {
                        $isConnected = true;
                        break;
                    }
                }
            }

            return response()->json(['isConnected' => $isConnected]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el estado de conexión'], 500);
        }
    }

}
