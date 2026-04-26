<?php

test('registration screen can be rendered', function () {
    $response = $this->get('http://dcms.lvh.me/register');

    $response->assertRedirect('http://dcms.lvh.me/admin/dashboard');
});

test('new users can register', function () {
    $response = $this->post('http://dcms.lvh.me/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('http://dcms.lvh.me');
});
