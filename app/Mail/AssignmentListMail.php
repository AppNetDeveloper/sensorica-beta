<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AssignmentListMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $assignmentUrl;

    /**
     * Create a new message instance.
     *
     * @param string $assignmentUrl
     */
    public function __construct(string $assignmentUrl)
    {
        $this->assignmentUrl = $assignmentUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject('Listado de Asignación')
            ->markdown('emails.assignment_list', [
                'url' => $this->assignmentUrl,
            ]);
    }
}
