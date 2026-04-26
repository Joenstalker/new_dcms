<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
$session = $stripe->checkout->sessions->retrieve('cs_test_a1nzObqmC7D6CPSONG2Qd1IHqUk2qZzF1Qian31T6yXlH0wKKSoJEbNcA8', ['expand' => ['subscription.latest_invoice']]);
echo json_encode([
    'subscription_PI' => $session->subscription->latest_invoice->payment_intent ?? null,
    'invoice' => $session->invoice ?? null,
    'payment_intent' => $session->payment_intent ?? null
]);
