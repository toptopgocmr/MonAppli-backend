<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $otp,
        protected string $type = 'verification'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->type === 'reset'
            ? 'Réinitialisation de mot de passe - TopTopGo'
            : 'Vérification de votre numéro - TopTopGo';

        $message = $this->type === 'reset'
            ? 'Votre code de réinitialisation est :'
            : 'Votre code de vérification est :';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->first_name . ' !')
            ->line($message)
            ->line('**' . $this->otp . '**')
            ->line('Ce code expire dans 10 minutes.')
            ->line('Si vous n\'avez pas demandé ce code, ignorez ce message.')
            ->salutation('L\'équipe TopTopGo');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'otp',
            'otp_type' => $this->type,
            'message' => 'Code OTP envoyé',
        ];
    }

    /**
     * Get SMS content for providers like Peex
     */
    public function toSms(object $notifiable): string
    {
        if ($this->type === 'reset') {
            return "TopTopGo: Votre code de réinitialisation est {$this->otp}. Valide 10 min.";
        }

        return "TopTopGo: Votre code de vérification est {$this->otp}. Valide 10 min.";
    }
}
