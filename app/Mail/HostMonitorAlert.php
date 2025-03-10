<?php

namespace App\Mail;

use App\Models\HostList;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HostMonitorAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $host;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\HostList $host
     */
    public function __construct(HostList $host)
    {
        $this->host = $host;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
         return $this->subject("Alerta: Sin registros recientes para {$this->host->name}")
                     ->markdown('emails.host_monitor_alert');
    }
}
