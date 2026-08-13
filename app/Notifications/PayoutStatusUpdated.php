<?php

namespace App\Notifications;

use App\Enums\PayoutStatus;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutStatusUpdated extends Notification
{
    public function __construct(
        private readonly string $amount,
        private readonly PayoutStatus $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->status === PayoutStatus::Paid ? 'paid' : 'failed';

        return (new MailMessage)
            ->subject("Your payout was {$verb}")
            ->greeting("Hi {$notifiable->name},")
            ->line('Your payout of '.money($this->amount)." was {$verb}.")
            ->action('View my earnings', route('creator.earnings.index'));
    }

    public function toArray(object $notifiable): array
    {
        $verb = $this->status === PayoutStatus::Paid ? 'paid' : 'failed';

        return [
            'message' => 'Your payout of '.money($this->amount)." was {$verb}.",
            'url' => route('creator.earnings.index'),
        ];
    }
}
