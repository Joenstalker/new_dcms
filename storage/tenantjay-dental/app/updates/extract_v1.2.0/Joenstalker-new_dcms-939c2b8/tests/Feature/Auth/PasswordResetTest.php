<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('http://dcms.lvh.me/forgot-password');

    $response->assertRedirect('http://dcms.lvh.me/admin/dashboard');
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('http://dcms.lvh.me/forgot-password', ['email' => $user->email]);

    Notification::assertNothingSent();
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('http://dcms.lvh.me/forgot-password', ['email' => $user->email]);

    Notification::assertNothingSent();

    $response = $this->get('http://dcms.lvh.me/reset-password/test-token');
    $response->assertRedirect('http://dcms.lvh.me/admin/dashboard');
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('http://dcms.lvh.me/reset-password', [
        'token' => 'test-token',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('http://dcms.lvh.me/admin/dashboard');
});
