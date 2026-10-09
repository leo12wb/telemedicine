<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentBooked extends Notification implements ShouldQueue
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
        $date        = $appointment->scheduled_date->format('d/m/Y');
        $time        = substr($appointment->scheduled_time, 0, 5);
        $doctor      = $appointment->doctor->user->name ?? 'Médico';
        $patient     = $appointment->patient->user->name ?? 'Paciente';

        return (new MailMessage)
            ->subject('Consulta agendada — ' . $date . ' às ' . $time)
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line("Uma consulta foi agendada com sucesso.")
            ->line("**Médico:** {$doctor}")
            ->line("**Paciente:** {$patient}")
            ->line("**Data:** {$date} às {$time}")
            ->line("**Duração:** {$appointment->duration_minutes} minutos")
            ->action('Ver consulta', url('/appointments'))
            ->line('Obrigado por usar o sistema de telemedicina.');
    }
}
