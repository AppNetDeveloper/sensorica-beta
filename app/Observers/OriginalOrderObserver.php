<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OriginalOrder;
use App\Models\CustomerClient;
use App\Models\RouteName;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OriginalOrderObserver
{
    /**
     * Handle the OriginalOrder "created" event.
     */
    public function created(OriginalOrder $order): void
    {
        // Si ya tiene cliente asociado o no hay customer_id, no hacemos nada
        if ($order->customer_client_id || !$order->customer_id) {
            return;
        }

        // Extraer datos posibles del JSON order_details
        $details = $order->order_details;
        if (is_string($details)) {
            $decoded = json_decode($details, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $details = $decoded;
            } else {
                $details = [];
            }
        }

        // Candidatos de nombre y datos del cliente
        $candidateName = $order->client_number ?: null;
        $candidateName = $candidateName ?: Arr::get($details, 'cliente.nombre');
        $candidateAddress = Arr::get($details, 'cliente.direccion')
            ?: Arr::get($details, 'cliente.address')
            ?: $order->address;
        $candidatePhone = Arr::get($details, 'cliente.telefono')
            ?: Arr::get($details, 'cliente.phone')
            ?: $order->phone;
        $candidateEmail = Arr::get($details, 'cliente.email');
        $candidateTaxId = Arr::get($details, 'cliente.nif')
            ?: Arr::get($details, 'cliente.tax_id')
            ?: $order->cif_nif;

        // Si no tenemos al menos un nombre identificador, no crear automáticamente
        if (!$candidateName && !$candidateTaxId) {
            return;
        }

        // Buscar cliente existente por (customer_id + tax_id) o por (customer_id + name)
        $query = CustomerClient::query()->where('customer_id', $order->customer_id);
        if ($candidateTaxId) {
            $query->where('tax_id', $candidateTaxId);
        } else {
            $query->where('name', $candidateName);
        }

        $client = $query->first();

        if (!$client) {
            // Crear registro mínimo con lo que tengamos
            $client = CustomerClient::create([
                'customer_id' => $order->customer_id,
                'route_name_id' => null,
                'name' => $candidateName ?: ($candidateEmail ?: ($candidatePhone ?: 'Cliente sin nombre')),
                'address' => $candidateAddress,
                'phone' => $candidatePhone,
                'email' => $candidateEmail,
                'tax_id' => $candidateTaxId,
                'active' => true,
            ]);
        }

        // Asignar y guardar sin disparar eventos
        $order->customer_client_id = $client->id;
        $order->saveQuietly();

        // Si hay route_name_id en la orden, enlazar/actualizar la ruta del CustomerClient
        // (si pertenece al mismo customer). Se actualiza incluso si ya tenía otra ruta.
        if ($order->route_name_id) {
            $route = RouteName::find($order->route_name_id);
            if ($route && $route->customer_id === $order->customer_id && $client->route_name_id !== $route->id) {
                $client->route_name_id = $route->id;
                $client->saveQuietly();
            }
        }
    }

    /**
     * Handle the OriginalOrder "updated" event.
     */
    public function updated(OriginalOrder $order): void
    {
        // Solo actuar si existen ambos IDs y pertenecen al mismo customer
        if (!$order->customer_id || !$order->customer_client_id || !$order->route_name_id) {
            return;
        }

        $client = CustomerClient::find($order->customer_client_id);
        if (!$client || $client->customer_id !== $order->customer_id) {
            return;
        }

        // Actualizar siempre la ruta del cliente a la de la orden si pertenece al mismo customer
        $route = RouteName::find($order->route_name_id);
        if ($route && $route->customer_id === $order->customer_id && $client->route_name_id !== $route->id) {
            $client->route_name_id = $route->id;
            $client->saveQuietly();
        }
    }
}
