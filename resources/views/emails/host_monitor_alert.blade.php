@component('mail::message')
# Alerta de Monitoreo

No se han recibido registros en la tabla **host_monitors** para el servidor **{{ $host->name }}** en los últimos 3 minutos.

Por favor, verifique el estado del servidor.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
