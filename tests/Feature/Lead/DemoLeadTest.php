<?php

use App\Modules\Lead\Models\Lead;
use App\Modules\Lead\Notifications\DemoRequestReceivedNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('the demo page renders for guests', function () {
    $response = $this->get(route('demo'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('demo'));
});

test('a guest can submit the demo form, lead is stored and notification is sent', function () {
    Notification::fake();

    config(['services.demo.notification_emails' => 'ggomez@valolabs.site,hola@valolabs.site']);

    $response = $this->post(route('demo.store'), [
        'name' => 'Carlos Perez',
        'email' => 'carlos@example.com',
        'phone' => '+584121234567',
    ]);

    $response->assertRedirect(route('demo'));
    $response->assertSessionHas('success');

    $lead = Lead::where('email', 'carlos@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->name)->toBe('Carlos Perez')
        ->and($lead->email)->toBe('carlos@example.com')
        ->and($lead->phone)->toBe('+584121234567')
        ->and($lead->status)->toBe(Lead::STATUS_PENDING);

    Notification::assertSentOnDemand(
        DemoRequestReceivedNotification::class,
        function (DemoRequestReceivedNotification $notification, array $channels, $notifiable) use ($lead) {
            return in_array($notifiable->routes['mail'], ['ggomez@valolabs.site', 'hola@valolabs.site'], true)
                && $notification->lead->id === $lead->id;
        }
    );

    Notification::assertSentOnDemandTimes(DemoRequestReceivedNotification::class, 2);
});

test('the demo form requires name, email and phone', function () {
    $response = $this->post(route('demo.store'), [
        'name' => '',
        'email' => '',
        'phone' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'phone']);
});
