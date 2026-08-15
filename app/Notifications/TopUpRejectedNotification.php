<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TopUpRejectedNotification extends Notification
{
    use Queueable;
    protected $topUpRequest;
    protected $reason;
    /**
     * Create a new notification instance.
     */
    public function __construct($topUpRequest, $reason = null)
    {
        $this->topUpRequest = $topUpRequest;
        $this->reason = $reason ?? 'We will provide you with the details of the rejection later. ';
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
            'title' => 'Wallet top-up request rejected.',
            'message' => 'Sorry, your wallet has not been topped up.',
            'amount' => $this->topUpRequest->amount,
            'reason' => $this->reason,
            'requested_at' => $this->topUpRequest->requested_at,
            'status' => 'rejected',
            'type' => 'topup_rejected'
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
