<?php

declare(strict_types=1);

namespace App\Modules\Lead\Notifications;

use App\Modules\Lead\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoRequestReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Lead $lead,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Nueva solicitud de demo — ' . config('app.name', 'ValolabsPOS'))
            ->greeting('¡Hola!')
            ->line('Se ha recibido una nueva solicitud para acceder a la demo de la plataforma:')
            ->line("**Nombre:** {$this->lead->name}")
            ->line("**Correo electrónico:** {$this->lead->email}")
            ->line("**Teléfono / WhatsApp:** {$this->lead->phone}")
            ->line('**Fecha:** ' . now()->format('d/m/Y H:i:s'))
            ->replyTo($this->lead->email, $this->lead->name)
            ->action('Responder por correo', 'mailto:' . $this->lead->email)
            ->salutation('Equipo de ' . config('app.name', 'ValolabsPOS'));

        return $mail;
    }
}
