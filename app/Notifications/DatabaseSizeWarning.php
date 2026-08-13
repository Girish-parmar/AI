<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DatabaseSizeWarning extends Notification
{
    public function __construct(
        private readonly string $sizeLabel,
        private readonly string $capLabel,
        private readonly int $percentUsed,
        private readonly bool $critical,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severity = $this->critical ? 'CRITICAL' : 'Warning';
        $advice = $this->critical
            ? 'The migration threshold from the hosting audit has been crossed — plan the move to a Cloud/VPS tier now.'
            : 'Start archiving old audit logs and notifications, and plan capacity before the critical threshold.';

        return (new MailMessage)
            ->subject("{$severity}: database at {$this->percentUsed}% of the hosting plan's cap")
            ->greeting("Hi {$notifiable->name},")
            ->line("The application database is {$this->sizeLabel}, {$this->percentUsed}% of the {$this->capLabel} per-database cap on the current hosting plan.")
            ->line($advice);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Database at {$this->percentUsed}% of the hosting plan's {$this->capLabel} cap ({$this->sizeLabel}).",
            'url' => route('superadmin.dashboard'),
        ];
    }
}
