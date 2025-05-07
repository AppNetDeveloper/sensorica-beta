@component('mail::message')
{{-- Saludo --}}
# ¡Buenos días!

Adjuntamos el *Listado de Asignación* de puestos. Por favor, revisa la información y accede al detalle pulsando el botón a continuación.

{{-- Botón grande --}}
@component('mail::button', ['url' => $url, 'color' => 'primary'])
Ver Listado de Asignación
@endcomponent

{{-- Pie de página --}}
Gracias por tu atención,<br>
{{ config('app.name') }}
@endcomponent
