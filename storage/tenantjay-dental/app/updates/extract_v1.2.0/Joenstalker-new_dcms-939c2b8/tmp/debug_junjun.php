<?php
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;

$tenant = Tenant::find('junjunsmile');
if (!$tenant) {
    echo "Tenant junjunsmile not found!\n";
    exit;
}
tenancy()->initialize($tenant);

echo "INVOICES:\n";
foreach (Invoice::all() as $i) {
    echo "ID: {$i->id} | Total: {$i->total_amount} | Paid: {$i->amount_paid} | Status: {$i->status} | Created: {$i->created_at}\n";
}

echo "\nPAYMENTS:\n";
foreach (Payment::all() as $p) {
    echo "ID: {$p->id} | Invoice ID: {$p->invoice_id} | Amount: {$p->amount} | Date: {$p->payment_date}\n";
}
