{{-- Usando la sintaxis @component compatible con Laravel 8 --}}
@component('mail::message')
# Producción por Trabajador y Confecciones Asignadas turno: ({{ $reportDate }})

Estimado/a usuario/a,

Adjunto encontrará los enlaces para descargar el informe de actividad de los operadores correspondiente al día **{{ $reportDate }}**.

- [📄 Descargar Informe en formato Excel]({{ $excelUrl }})
- [📑 Descargar Informe en formato PDF]({{ $pdfUrl }})

---

**Acceso al Historial Completo**

Si desea consultar informes de días anteriores (hasta los últimos 30 días), le recomendamos utilizar nuestra interfaz web interactiva.

@component('mail::button', ['url' => $webInterfaceUrl])
💻 Acceder a la Interfaz Web de Informes
@endcomponent

Gracias por utilizar nuestros servicios.

Atentamente,<br>
El equipo de {{ config('app.name') }}
@endcomponent

{{-- Opcional: Añadir un pie de página si es necesario --}}
{{--
@slot('subcopy')
Si tiene problemas con los botones de descarga, puede copiar y pegar las siguientes URLs directamente en su navegador web:
- Informe Excel: [{{ $excelUrl }}]({{ $excelUrl }})
- Informe PDF: [{{ $pdfUrl }}]({{ $pdfUrl }})
- Interfaz Web: [{{ $webInterfaceUrl }}]({{ $webInterfaceUrl }})
@endslot
--}}
