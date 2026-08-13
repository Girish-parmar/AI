<?php

namespace App\Notifications;

use App\Enums\PurchaseStatus;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseStatusUpdated extends Notification
{
    public function __construct(
        private readonly string $itemTitle,
        private readonly PurchaseStatus $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->status === PurchaseStatus::Completed ? 'completed' : 'failed';

        return (new MailMessage)
            ->subject("Your purchase was {$verb}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your purchase of \"{$this->itemTitle}\" was {$verb}.")
            ->action('View my purchases', route('user.purchases.index'));
    }

    public function toArray(object $notifiable): array
    {
        $verb = $this->status === PurchaseStatus::Completed ? 'completed' : 'failed';

        return [
            'message' => "Your purchase of \"{$this->itemTitle}\" was {$verb}.",
            'url' => route('user.purchases.index'),
        ];
    }
}
