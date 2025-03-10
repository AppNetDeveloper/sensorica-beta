@component('mail::message')
# Alerta del servidor: {{ $alertData['host'] }}

Se han detectado métricas críticas en el servidor:

- **CPU:** {{ $alertData['cpu'] }}%
- **Memoria usada:** {{ $alertData['memory_used_percent'] }}%
- **Uso de disco:** {{ $alertData['disk'] }}%

Por favor, revise el estado del servidor lo antes posible.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
