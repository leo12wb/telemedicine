<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $date = $appointment->scheduled_date->format('d/m/Y');
        $time = substr($appointment->scheduled_time, 0, 5);
        $reason = $appointment->cancellation_reason ?? 'Não informado';

        return (new MailMessage)
            ->subject('Consulta cancelada — '.$date.' às '.$time)
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('A consulta abaixo foi cancelada.')
            ->line("**Data:** {$date} às {$time}")
            ->line("**Motivo:** {$reason}")
            ->line('Se tiver dúvidas, entre em contato com nossa equipe.');
    }
}
