<?php

declare(strict_types=1);

namespace App\Modules\Store\Notifications;

use App\Modules\Store\Models\StoreCustomer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Enlace para que un cliente invitado desde el ERP ponga su contraseña en la
 * tienda. La URL apunta a `{store_url}/invitacion/{token}`.
 */
class StoreCustomerInvitation extends Notification
{
    public function __construct(
        public readonly string $storeName,
        public readonly string $url,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(StoreCustomer $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(StoreCustomer $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Te invitaron a comprar en {$this->storeName}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("{$this->storeName} te abrió una cuenta en su tienda en línea. Ponle una contraseña para empezar a comprar con tus precios y direcciones de siempre.")
            ->action('Activar mi cuenta', $this->url)
            ->line('El enlace vence en 7 días. Si no esperabas esta invitación, ignora este correo.');
    }
}
