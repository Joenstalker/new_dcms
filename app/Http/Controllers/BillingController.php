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
        $invoices = Invoice::with('patient')->latest()->get();
        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
            'patients' => Patient::select('id', 'first_name', 'last_name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'total_amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'required|string|in:unpaid,partially_paid,paid'
        ]);

        Invoice::create($validated);

        return redirect()->back()->with('success', 'Invoice created successfully.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:unpaid,partially_paid,paid',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $invoice->update($validated);

        return redirect()->back()->with('success', 'Invoice updated successfully.');
    }
}
