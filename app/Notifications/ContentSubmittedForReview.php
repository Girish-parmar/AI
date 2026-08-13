<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContentSubmittedForReview extends Notification
{
    /**
     * @param  'course'|'script'|'advertisement'  $contentType
     */
    public function __construct(
        private readonly string $contentType,
        private readonly string $contentTitle,
        private readonly string $creatorName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New {$this->contentType} awaiting review")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->creatorName} submitted a {$this->contentType} \"{$this->contentTitle}\" for review.")
            ->action('Review now', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->creatorName} submitted a {$this->contentType} \"{$this->contentTitle}\" for review.",
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return match ($this->contentType) {
            'course' => route('admin.courses.index', ['status' => 'pending']),
            'script' => route('admin.scripts.index', ['status' => 'pending']),
            'advertisement' => route('admin.advertisements.index', ['status' => 'pending']),
        };
    }
}
