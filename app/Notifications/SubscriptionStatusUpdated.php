<?php

namespace App\Notifications;

use App\Enums\SubscriptionStatus;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionStatusUpdated extends Notification
{
    public function __construct(
        private readonly string $planName,
        private readonly SubscriptionStatus $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->status === SubscriptionStatus::Active ? 'activated' : 'cancelled';

        return (new MailMessage)
            ->subject("Your subscription was {$verb}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your subscription to \"{$this->planName}\" was {$verb}.")
            ->action('View my subscription', route('user.subscription.show'));
    }

    public function toArray(object $notifiable): array
    {
        $verb = $this->status === SubscriptionStatus::Active ? 'activated' : 'cancelled';

        return [
            'message' => "Your subscription to \"{$this->planName}\" was {$verb}.",
            'url' => route('user.subscription.show'),
        ];
    }
}
