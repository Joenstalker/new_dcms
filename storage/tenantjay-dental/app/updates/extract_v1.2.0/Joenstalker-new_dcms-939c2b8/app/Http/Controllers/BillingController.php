<?php

namespace App\Http\Controllers;

use App\Events\TenantInvoiceChanged;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['patient.treatments', 'items'])->latest()->get();
        return Inertia::render('Tenant/Billing/Index', [
            'invoices' => $invoices,
            'patients' => Patient::with('treatments')->select('id', 'first_name', 'last_name')->get(),
            'services' => \App\Models\Service::where('status', 'approved')->select('id', 'name', 'price')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'total_amount' => 'required|numeric|min:0',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:unpaid,partially_paid,paid',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $invoice = Invoice::create([
            'patient_id' => $validated['patient_id'],
            'total_amount' => $validated['total_amount'],
            'amount_paid' => $validated['status'] === 'paid' ? $validated['total_amount'] : 0,
            'status' => $validated['status'],
            'due_date' => now(),
        ]);

        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        $this->broadcastInvoiceChange($invoice->fresh()->load(['patient.treatments', 'items']), 'created');

        return redirect()->back()->with('success', 'Invoice created successfully.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:unpaid,partially_paid,paid',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        if ($validated['status'] === 'paid' && !isset($validated['amount_paid'])) {
            $validated['amount_paid'] = $invoice->total_amount;
        }

        $invoice->update($validated);
        $this->broadcastInvoiceChange($invoice->fresh()->load(['patient.treatments', 'items']), 'updated');

        return redirect()->back()->with('success', 'Invoice updated successfully.');
    }

    private function broadcastInvoiceChange(Invoice $invoice, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $payload = [
            'id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'total_amount' => $invoice->total_amount,
            'amount_paid' => $invoice->amount_paid,
            'status' => $invoice->status,
            'due_date' => optional($invoice->due_date)?->toISOString(),
            'created_at' => optional($invoice->created_at)?->toISOString(),
            'updated_at' => optional($invoice->updated_at)?->toISOString(),
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                ];
            })->values()->all(),
            'patient' => $invoice->patient ? [
                'id' => $invoice->patient->id,
                'first_name' => $invoice->patient->first_name,
                'last_name' => $invoice->patient->last_name,
                'treatments' => $invoice->patient->treatments->map(function ($treatment) {
                    return [
                        'id' => $treatment->id,
                        'procedure' => $treatment->procedure,
                        'diagnosis' => $treatment->diagnosis,
                        'cost' => $treatment->cost,
                        'created_at' => optional($treatment->created_at)?->toISOString(),
                    ];
                })->values()->all(),
            ] : null,
        ];

        broadcast(new TenantInvoiceChanged((string) tenant()->getTenantKey(), $action, $payload));
    }
}
