<?php

namespace App\Notifications;

use App\Enums\ApprovalStatus;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContentReviewed extends Notification
{
    /**
     * @param  'course'|'script'|'advertisement'  $contentType
     */
    public function __construct(
        private readonly string $contentType,
        private readonly string $contentTitle,
        private readonly int $contentId,
        private readonly ApprovalStatus $status,
        private readonly ?string $notes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->status === ApprovalStatus::Approved ? 'approved' : 'rejected';

        $mail = (new MailMessage)
            ->subject("Your {$this->contentType} was {$verb}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your {$this->contentType} \"{$this->contentTitle}\" has been {$verb}.");

        if ($this->notes) {
            $mail->line("Reviewer notes: {$this->notes}");
        }

        return $mail->action('View details', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        $verb = $this->status === ApprovalStatus::Approved ? 'approved' : 'rejected';

        return [
            'message' => "Your {$this->contentType} \"{$this->contentTitle}\" was {$verb}.",
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return match ($this->contentType) {
            'course' => route('creator.courses.edit', $this->contentId),
            'script' => route('creator.scripts.edit', $this->contentId),
            'advertisement' => route('creator.advertisements.edit', $this->contentId),
        };
    }
}
