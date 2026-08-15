<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the landing page renders for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
