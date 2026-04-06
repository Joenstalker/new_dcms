<?php
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Carbon\Carbon;

$tenant = Tenant::find('junjunsmile');
if ($tenant) {
    tenancy()->initialize($tenant);
    echo "Initialized tenant: " . $tenant->id . "\n";
}
else {
    echo "Tenant 'junjunsmile' not found!\n";
    $tenant = Tenant::first();
    if ($tenant) {
        tenancy()->initialize($tenant);
        echo "Fallback to first tenant: " . $tenant->id . "\n";
    }
    else {
        echo "No tenants found at all!\n";
        exit;
    }
}

$startOfMonth = Carbon::now()->startOfMonth();

echo "Checking ALL Invoices:\n";
$invoices = Invoice::all();
foreach ($invoices as $inv) {
    echo "ID: {$inv->id} | Status: {$inv->status} | Amount Paid: {$inv->amount_paid} | Total: {$inv->total_amount} | Created: {$inv->created_at}\n";
}

echo "\nChecking ALL Payments:\n";
$payments = Payment::all();
foreach ($payments as $p) {
    echo "ID: {$p->id} | Invoice ID: {$p->invoice_id} | Amount: {$p->amount} | Created: {$p->created_at}\n";
}

$monthlyRevenueOld = Invoice::where('status', 'paid')
    ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
    ->sum('amount_paid');
echo "\nOld logic revenue sum: " . $monthlyRevenueOld . "\n";

$monthlyRevenueNew = Payment::whereBetween('created_at', [$startOfMonth, Carbon::now()])
    ->sum('amount');
echo "New logic revenue sum: " . $monthlyRevenueNew . "\n";
