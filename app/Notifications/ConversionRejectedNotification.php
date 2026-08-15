<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversionRejectedNotification extends Notification
{
    use Queueable;


    protected $conversionRequest;
    protected $reason;
    /**
     * Create a new notification instance.
     */
    public function __construct($conversionRequest, $reason = null)
    {
        $this->conversionRequest = $conversionRequest;
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
            'title' => 'Point transfer request rejected',
            'message' => 'Sorry, your points have not been converted into loyalty points.',
            'points_requested' => $this->conversionRequest->points_to_convert,
            'loyalty_points_expected' => $this->conversionRequest->loyalty_points_expected,
            'reason' => $this->reason,
            'requested_at' => $this->conversionRequest->requested_at,
            'type' => 'conversion_rejected',
            'status' => 'rejected',
            'created_at' => now(),
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
