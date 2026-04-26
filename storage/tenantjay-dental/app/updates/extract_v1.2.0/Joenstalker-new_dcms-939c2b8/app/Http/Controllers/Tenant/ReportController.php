<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function export(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:revenue,appointments,patients,treatments',
            'filter' => 'required|in:today,week,month,year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $type = $validated['type'];
        $filter = $validated['filter'];
        $dateRange = $this->getDateRange($filter, $request->start_date, $request->end_date);
        
        $data = $this->getReportData($type, $dateRange);
        $tenant = tenant();
        
        $randomStr = Str::upper(Str::random(8));
        $digitalSignature = now()->format('Ymd') . $randomStr;
        
        $pdf = Pdf::loadView('reports.export-pdf', [
            'type' => $type,
            'filter' => $filter,
            'dateRange' => $dateRange,
            'data' => $data,
            'tenant' => $tenant,
            'digitalSignature' => $digitalSignature,
            'generatedAt' => now()->format('Y-m-d h:i A'),
        ]);

        $pdf->setPaper('a4', 'landscape');
        
        $filename = "Clinic_Report_{$type}_{$filter}_" . now()->format('YmdHis') . ".pdf";
        
        return $pdf->download($filename);
    }

    private function getDateRange(string $filter, $start = null, $end = null): array
    {
        $now = Carbon::now();
        
        return match ($filter) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'TODAY (' . $now->format('Y-m-d') . ')'
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => 'THIS WEEK (' . $now->startOfWeek()->format('Y-m-d') . ' to ' . $now->endOfWeek()->format('Y-m-d') . ')'
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => 'THIS MONTH (' . $now->format('F Y') . ')'
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => 'THIS YEAR (' . $now->format('Y') . ')'
            ],
            'custom' => [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
                'label' => 'CUSTOM PERIOD (' . Carbon::parse($start)->format('Y-m-d') . ' to ' . Carbon::parse($end)->format('Y-m-d') . ')'
            ],
        };
    }

    private function getReportData(string $type, array $range): array
    {
        return match ($type) {
            'revenue' => Payment::with(['invoice.patient'])
                ->whereBetween('payment_date', [$range['start'], $range['end']])
                ->orderBy('payment_date', 'desc')
                ->get()
                ->map(fn($p) => [
                    'Date' => $p->payment_date->format('Y-m-d H:i'),
                    'Patient' => $p->invoice->patient->full_name ?? 'N/A',
                    'Method' => strtoupper($p->payment_method),
                    'Reference' => $p->reference_number ?? 'N/A',
                    'Amount' => '₱' . number_format($p->amount, 2),
                ])->toArray(),
                
            'appointments' => Appointment::with(['patient', 'dentist'])
                ->whereBetween('appointment_date', [$range['start'], $range['end']])
                ->orderBy('appointment_date', 'asc')
                ->get()
                ->map(fn($a) => [
                    'Time' => $a->appointment_date->format('Y-m-d H:i'),
                    'Patient' => $a->patient ? $a->patient->full_name : "{$a->guest_first_name} {$a->guest_last_name}",
                    'Dentist' => $a->dentist->name ?? 'N/A',
                    'Service' => $a->service ?? 'N/A',
                    'Status' => strtoupper($a->status),
                ])->toArray(),
                
            'patients' => Patient::whereBetween('created_at', [$range['start'], $range['end']])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($p) => [
                    'Registered' => $p->created_at->format('Y-m-d'),
                    'ID' => $p->patient_id,
                    'Name' => $p->full_name,
                    'Email' => $p->email ?? 'N/A',
                    'Mobile' => $p->phone ?? 'N/A',
                    'Gender' => strtoupper($p->gender ?? 'N/A'),
                ])->toArray(),

            'treatments' => Treatment::with(['patient', 'dentist', 'service'])
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($t) => [
                    'Date' => $t->created_at->format('Y-m-d'),
                    'Patient' => $t->patient->full_name ?? 'N/A',
                    'Dentist' => $t->dentist->name ?? 'N/A',
                    'Procedure' => $t->procedure ?? $t->service?->name ?? 'N/A',
                    'Cost' => '₱' . number_format($t->cost, 2),
                    'Paid' => '₱' . number_format($t->amount_paid, 2),
                ])->toArray(),
        };
    }
}
