<?php

namespace App\Http\Controllers;

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

        return redirect()->back()->with('success', 'Invoice updated successfully.');
    }
}
