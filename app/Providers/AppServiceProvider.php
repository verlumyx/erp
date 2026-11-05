<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Recuperación de contraseña — '.config('app.name', 'Valolabs POS'))
                ->greeting('¡Hola!')
                ->line('Recibiste este correo porque se solicitó un restablecimiento de contraseña para tu cuenta en '.config('app.name', 'Valolabs POS').'.')
                ->action('Restablecer contraseña', $url)
                ->line('Este enlace de recuperación caducará en '.(config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60)).' minutos.')
                ->line('Si no realizaste esta solicitud, puedes ignorar este mensaje de forma segura.');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
