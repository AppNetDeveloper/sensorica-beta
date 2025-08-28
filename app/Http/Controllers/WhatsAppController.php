<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    /**
     * Muestra la vista para notificaciones de WhatsApp.
     */
    public function sendNotification()
    {
        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/whatsapp-qr/base64';
    
        try {
            $response = Http::withoutVerifying()->get($apiUrl);
            $data = $response->json();
    
            // Para depurar, descomenta la siguiente línea y revisa la respuesta:
            // dd($data);
    
            $isLinked = (isset($data['success']) && $data['success'] === true) ? false : true;
            $qrCode = (isset($data['qr_image']) && $data['success'] === true) ? $data['qr_image'] : null;
    
            return view('whatsapp.notification', [
                'isLinked' => $isLinked,
                'qrCode' => $qrCode,
                'phoneNumber' => env('WHATSAPP_PHONE_NOT'),
                'phoneNumberMaintenance' => env('WHATSAPP_PHONE_MANTENIMIENTO'),
            ]);
        } catch (\Exception $e) {
            return view('whatsapp.notification', [
                'isLinked' => false,
                'qrCode' => null,
                'phoneNumber' => env('WHATSAPP_PHONE_NOT'),
                'phoneNumberMaintenance' => env('WHATSAPP_PHONE_MANTENIMIENTO'),
            ]);
        }
    }
    

    /**
     * Desconectar WhatsApp.
     */
    public function disconnect()
    {
        try {
            Http::withoutVerifying()->post(rtrim(env('LOCAL_SERVER'), '/') . '/api/whatsapp-disconnect');
            return redirect()->route('whatsapp.notifications')->with('status', 'WhatsApp desconectado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('whatsapp.notifications')->with('error', 'Error al desconectar WhatsApp.');
        }
    }
    

    /**
     * Actualizar el número de notificación en .env.
     */
    public function updatePhoneNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15',
        ]);

        $newPhone = $request->input('phone_number');
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (preg_match('/^WHATSAPP_PHONE_NOT=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^WHATSAPP_PHONE_NOT=.*$/m',
                "WHATSAPP_PHONE_NOT={$newPhone}",
                $envContent
            );
        } else {
            $envContent .= (str_ends_with($envContent, "\n") ? '' : "\n") . "WHATSAPP_PHONE_NOT={$newPhone}\n";
        }

        file_put_contents($envPath, $envContent);

        return redirect()->route('whatsapp.notifications')->with('status', 'Número de notificación actualizado correctamente.');
    }

    /**
     * Actualizar los números de mantenimiento (separados por coma) en .env.
     */
    public function updateMaintenancePhones(Request $request)
    {
        $request->validate([
            // Permitir lista separada por comas, espacios opcionales; hasta ~200 chars
            'maintenance_phones' => 'required|string|max:200',
        ]);

        $newPhones = trim($request->input('maintenance_phones'));
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (preg_match('/^WHATSAPP_PHONE_MANTENIMIENTO=.*/m', $envContent)) {
            $envContent = preg_replace(
                '/^WHATSAPP_PHONE_MANTENIMIENTO=.*$/m',
                "WHATSAPP_PHONE_MANTENIMIENTO={$newPhones}",
                $envContent
            );
        } else {
            $envContent .= (str_ends_with($envContent, "\n") ? '' : "\n") . "WHATSAPP_PHONE_MANTENIMIENTO={$newPhones}\n";
        }

        file_put_contents($envPath, $envContent);

        return redirect()->route('whatsapp.notifications')->with('status', 'Teléfonos de mantenimiento actualizados correctamente.');
    }

    /**
     * Enviar un mensaje de prueba.
     */
    public function sendTestMessage(Request $request)
    {
        $request->validate([
            'test_phone_number' => 'required|string|max:15',
            'test_message' => 'required|string|max:255',
        ]);

        $phoneNumber = $request->input('test_phone_number');
        $message = $request->input('test_message');
        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . "/api/send-message";

        try {
            $response = Http::withoutVerifying()->get($apiUrl, [
                'jid' => $phoneNumber . '@s.whatsapp.net',
                'message' => $message,
            ]);

            if ($response->successful()) {
                return redirect()->route('whatsapp.notifications')->with('status', 'Mensaje enviado exitosamente.');
            }

            return redirect()->route('whatsapp.notifications')->with('error', 'No se pudo enviar el mensaje. Verifica el número y el mensaje.');
        } catch (\Exception $e) {
            return redirect()->route('whatsapp.notifications')->with('error', 'Error al conectar con la API. Intenta nuevamente.');
        }
    }
    /**
     * Devuelve el estado de conexión de WhatsApp en formato JSON.
     */
    public function getStatus()
    {
        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/whatsapp-qr/base64';

        try {
            $response = Http::withoutVerifying()->get($apiUrl);
            $data = $response->json();

            $isLinked = (isset($data['success']) && $data['success'] === true) ? false : true;
            $qrCode = (isset($data['qr_image']) && $data['success'] === true) ? $data['qr_image'] : null;

            return response()->json([
                'isLinked' => $isLinked,
                'qrCode' => $qrCode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'isLinked' => false,
                'qrCode' => null,
            ], 500);
        }
    }


}
 