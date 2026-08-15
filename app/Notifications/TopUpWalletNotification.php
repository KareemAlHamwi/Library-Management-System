<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TopUpWalletNotification extends Notification
{
    use Queueable;
    protected $transaction;
    protected $type;
    /**
     * Create a new notification instance.
     */
    public function __construct($transaction, $type = 'approved')
    {
        $this->transaction = $transaction;
        $this->type = $type;
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
            'title' => 'Top Up process',
            'message' => 'Your Wallet has been topped up with amount' . $this->transaction->amount . ' ' . $this->transaction->wallet->currency,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->wallet->currency,
            'new_balance' => $this->transaction->wallet->balance,
            'transaction_id' => $this->transaction->id,
            'type' => 'topup_completed',
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
