<?php

declare(strict_types=1);

namespace App\Modules\Lead\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Lead\Commands\CreateLeadCommand;
use App\Modules\Lead\Models\Lead;
use App\Modules\Lead\Notifications\DemoRequestReceivedNotification;
use App\Modules\Lead\Requests\CreateLeadRequest;
use App\Modules\Lead\Services\LeadCreateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class DemoPostController extends Controller
{
    public function __construct(
        private readonly LeadCreateService $createService,
    ) {}

    public function __invoke(CreateLeadRequest $request): RedirectResponse
    {
        $lead = $this->createService->execute(
            CreateLeadCommand::fromRequest($request)
        );

        $this->notifyRecipients($lead);

        return redirect()->route('demo')
            ->with('success', '¡Solicitud recibida! Nuestro equipo se pondrá en contacto contigo para coordinar el acceso a la demo.');
    }

    private function notifyRecipients(Lead $lead): void
    {
        $rawEmails = (string) config('services.demo.notification_emails', 'ggomez@valolabs.site,hola@valolabs.site');

        $emails = array_filter(
            array_map('trim', explode(',', $rawEmails)),
            static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        );

        if ($emails === []) {
            return;
        }

        try {
            foreach ($emails as $recipient) {
                Notification::route('mail', $recipient)->notify(new DemoRequestReceivedNotification($lead));
            }
        } catch (Throwable $e) {
            Log::error('Error al enviar correo de notificación para demo', [
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

