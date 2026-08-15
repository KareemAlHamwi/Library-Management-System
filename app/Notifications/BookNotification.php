<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function Laravel\Prompts\title;

class BookNotification extends Notification
{
    use Queueable;
protected  $book;
    /**
     * Create a new notification instance.
     */
    public function __construct( $book)
    {
        $this->book=$book;
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
            'title' => $this->book->title,
            'authors'=>$this->book->authors,
            'category'=>$this->book->categories,
            'description' => $this->book->description,
            'publisher' => $this->book->publisher,
            'published_date' => $this->book->published_date,
            'page_count' => $this->book->page_count,
            'language' => $this->book->language,
            'price' => $this->book->price,

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
