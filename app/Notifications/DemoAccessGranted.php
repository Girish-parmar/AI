<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoAccessGranted extends Notification
{
    public function __construct(
        private readonly string $resourceTitle,
        private readonly string $url,
        private readonly \DateTimeInterface $expiresAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have new demo access')
            ->greeting("Hi {$notifiable->name},")
            ->line("You've been granted demo access to \"{$this->resourceTitle}\" until {$this->expiresAt->format('M j, Y')}.")
            ->action('View it now', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "You've been granted demo access to \"{$this->resourceTitle}\" until {$this->expiresAt->format('M j, Y')}.",
            'url' => $this->url,
        ];
    }
}
