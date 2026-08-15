<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversionApprovedNotification extends Notification
{
    use Queueable;
    protected $conversionRequest;
    protected $user;
    /**
     * Create a new notification instance.
     */
    public function __construct($conversionRequest)
    {
        $this->conversionRequest = $conversionRequest;
        $this->user = $conversionRequest->user;
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
            'title' => ' Points have been converted successfully',
            'message' => 'Convet ' . $this->conversionRequest->points_to_convert . ' purchase points' . $this->conversionRequest->loyalty_points_expected . ' loyalty points(SP)',
            'points_converted' => $this->conversionRequest->points_to_convert,
            'loyalty_points_earned' => $this->conversionRequest->loyalty_points_expected,
            'remaining_purchase_points' => $this->user->purchase_points,
            'new_loyalty_points' => $this->user->loyalty_points,
            'type' => 'conversion_approved',
            'status' => 'completed',
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
