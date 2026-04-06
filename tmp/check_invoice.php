<?php
$tenant = \App\Models\Tenant::find('junjunsmile');
tenancy()->initialize($tenant);

$invoice = \App\Models\Invoice::with(['items', 'patient.treatments'])->find(2);

if (!$invoice) {
    echo "Invoice #2 not found.\n";
    exit;
}

echo "Invoice ID: " . $invoice->id . "\n";
echo "Total Amount: " . $invoice->total_amount . "\n";
echo "Items Count: " . $invoice->items->count() . "\n";
foreach ($invoice->items as $item) {
    echo "- Item: " . $item->description . " (Qty: " . $item->quantity . ")\n";
}

echo "Patient: " . ($invoice->patient ? $invoice->patient->first_name . ' ' . $invoice->patient->last_name : "None") . "\n";
echo "Treatments Count: " . ($invoice->patient ? $invoice->patient->treatments->count() : 0) . "\n";
foreach ($invoice->patient->treatments as $t) {
    echo "- Treatment: " . $t->procedure . "\n";
}
