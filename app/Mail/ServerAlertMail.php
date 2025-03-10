<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServerAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alertData;

    /**
     * Create a new message instance.
     *
     * @param array $alertData
     */
    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Alerta: Métricas críticas del servidor')
                    ->markdown('emails.server_alert');
    }
}
