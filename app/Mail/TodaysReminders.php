<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TodaysReminders extends Mailable
{
    use Queueable, SerializesModels;

    protected $reminders;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reminders)
    {
        $this->reminders = $reminders;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.reminders')
            ->with([
                'shop_reminders' => $this->reminders['shops'],
                'manager_reminders' => $this->reminders['managers']
            ]);
    }
}
