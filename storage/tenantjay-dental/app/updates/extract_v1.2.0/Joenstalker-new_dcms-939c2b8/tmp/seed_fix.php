<?php
$tenant = \App\Models\Tenant::find('junjunsmile');
tenancy()->initialize($tenant);

$invoice = \App\Models\Invoice::find(2);
$patient = \App\Models\Patient::find($invoice->patient_id);

if ($invoice && $invoice->items->count() === 0) {
    echo "Seeding items for Invoice #2...\n";
    $invoice->items()->create([
        'description' => 'Tooth Extraction (Sample Data)',
        'quantity' => 1,
        'unit_price' => 490.00,
        'amount' => 490.00,
    ]);
}

if ($patient && $patient->treatments->count() === 0) {
    echo "Seeding a treatment for patient " . $patient->first_name . "...\n";
    $patient->treatments()->create([
        'procedure' => 'Initial Consultation (Sample Data)',
        'diagnosis' => 'General Checkup',
        'cost' => 0,
        'notes' => 'Diagnostic seed',
    ]);
}

echo "Done seeding diagnostic data.\n";
