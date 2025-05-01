<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Opcional: para logs si es necesario

class OperatorReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Propiedades públicas para pasar datos a la vista
    public string $excelUrl;
    public string $pdfUrl;
    public string $webInterfaceUrl; // <-- NUEVA PROPIEDAD
    public string $reportDate;

    /**
     * Create a new message instance.
     *
     * @param string $excelUrl URL para descargar el Excel.
     * @param string $pdfUrl URL para descargar el PDF.
     * @param string $webInterfaceUrl URL para la interfaz web. // <-- NUEVO PARÁMETRO
     * @param string $reportDate Fecha del informe (YYYY-MM-DD).
     */
    public function __construct(string $excelUrl, string $pdfUrl, string $webInterfaceUrl, string $reportDate)
    {
        Log::info('OperatorReportMail Constructor: Recibiendo datos', [ // Log opcional
            'excel' => $excelUrl,
            'pdf' => $pdfUrl,
            'web' => $webInterfaceUrl, // <-- Log del nuevo parámetro
            'date' => $reportDate
        ]);
        $this->excelUrl   = $excelUrl;
        $this->pdfUrl     = $pdfUrl;
        $this->webInterfaceUrl = $webInterfaceUrl; // <-- ASIGNAR NUEVO PARÁMETRO
        $this->reportDate = $reportDate;
    }

    /**
     * Build the message.
     * (Usando el método build() que te funcionó)
     *
     * @return $this
     */
    public function build()
    {
        Log::info('OperatorReportMail: Ejecutando método build().'); // Log opcional
        $formattedDate = \Carbon\Carbon::parse($this->reportDate)->format('d/m/Y');

        return $this
            ->subject("Informe diario de producción y asignaciones ({$formattedDate})")
            ->markdown('emails.operator_report', [
                'excelUrl'   => $this->excelUrl,
                'pdfUrl'     => $this->pdfUrl,
                'webInterfaceUrl' => $this->webInterfaceUrl, // <-- PASAR NUEVA VARIABLE A LA VISTA
                'reportDate' => $formattedDate, // Pasamos la fecha formateada
            ]);
    }

    // Si estuvieras usando Laravel 9+ con envelope() y content(),
    // no necesitarías pasar las variables explícitamente en 'with'
    // si son propiedades públicas, pero sí añadir la propiedad y actualizar el constructor.
}
