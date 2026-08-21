<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function Laravel\Prompts\title;

class EventNotification extends Notification
{
    use Queueable;
protected  $event;
    /**
     * Create a new notification instance.
     */
    public function __construct( $event)
    {
        $this->event=$event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }
    public function toDatabase(object $notifiable): array
    {
        return [
            'supervisor_id' => $this->event->supervisor_id,
            'book_id'=>$this->event->book_id,
            'title'=>$this->event->title,
            'description' => $this->event->description,
            'prompt' => $this->event->prompt,
            'external_link' => $this->event->external_link,
            'status' => $this->event->status,
            'starts_at' => $this->event->starts_at,
            'ends_at' => $this->event->ends_at,

        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    // public function toArray(object $notifiable): array
    // {
    //     return [
    //         //
    //     ];
    // }
}
