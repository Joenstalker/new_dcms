<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('http://dcms.lvh.me/login');

    $response->assertRedirect('http://dcms.lvh.me/admin/dashboard');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->post('http://dcms.lvh.me/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('http://dcms.lvh.me/admin/dashboard');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->post('http://dcms.lvh.me/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->post('http://dcms.lvh.me/logout');

    $this->assertGuest();
    $response->assertRedirect('http://dcms.lvh.me');
});
