<?php

use App\Modules\Lead\Models\Lead;
use Inertia\Testing\AssertableInertia as Assert;

test('the contact page renders for guests', function () {
    $response = $this->get(route('contact'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('contact'));
});

test('a guest can submit the contact form and a pending lead is stored', function () {
    $response = $this->post(route('contact.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+56912345678',
    ]);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHas('success');

    $lead = Lead::first();

    expect($lead)->not->toBeNull()
        ->and($lead->name)->toBe('Ada Lovelace')
        ->and($lead->email)->toBe('ada@example.com')
        ->and($lead->phone)->toBe('+56912345678')
        ->and($lead->status)->toBe(Lead::STATUS_PENDING);
});

test('the contact form requires name, email and phone', function () {
    $response = $this->post(route('contact.store'), [
        'name' => '',
        'email' => '',
        'phone' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'phone']);

    expect(Lead::count())->toBe(0);
});

test('the contact form rejects an invalid email', function () {
    $response = $this->post(route('contact.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'not-an-email',
        'phone' => '+56912345678',
    ]);

    $response->assertSessionHasErrors(['email']);

    expect(Lead::count())->toBe(0);
});
