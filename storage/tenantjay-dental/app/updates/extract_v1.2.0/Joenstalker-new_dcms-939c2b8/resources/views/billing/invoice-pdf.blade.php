<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .container { border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 14px; }
        .logo { font-size: 20px; font-weight: 700; color: #1d4ed8; }
        .logo-img { height: 38px; max-width: 180px; object-fit: contain; }
        .title { font-size: 20px; font-weight: 700; margin-top: 5px; }
        .meta td { padding: 3px 0; }
        .meta .label { color: #6b7280; width: 120px; }
        .billto { margin-top: 14px; background: #f9fafb; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 8px; }
        table.items th { background: #f3f4f6; color: #374151; font-size: 11px; text-transform: uppercase; }
        .right { text-align: right; }
        .totals { margin-top: 14px; width: 280px; margin-left: auto; }
        .totals td { padding: 4px 0; }
        .totals .label { color: #6b7280; }
        .totals .grand { font-size: 15px; font-weight: 700; border-top: 1px solid #d1d5db; padding-top: 6px; }
        .note { margin-top: 12px; font-size: 11px; color: #4b5563; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('image/dcms-logo.png');
        if (! file_exists($logoPath)) {
            $logoPath = public_path('images/dcms-logo.png');
        }
    @endphp

    <div class="container">
        <div class="header">
            <div>
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="DCMS Logo" class="logo-img">
                @else
                    <div class="logo">DCMS LOGO</div>
                @endif
                <div class="title">Invoice</div>
            </div>
            <table class="meta">
                <tr><td class="label">Invoice #</td><td>{{ $invoiceNumber }}</td></tr>
                <tr><td class="label">Invoice Date</td><td>{{ \Illuminate\Support\Carbon::parse($invoiceDate)->format('Y-m-d') }}</td></tr>
                <tr><td class="label">Terms</td><td>Due in 7 days</td></tr>
                <tr><td class="label">Due Date</td><td>{{ \Illuminate\Support\Carbon::parse($dueDate)->format('Y-m-d') }}</td></tr>
                <tr><td class="label">Currency</td><td>{{ strtoupper((string) ($history->currency ?? 'PHP')) }}</td></tr>
            </table>
        </div>

        <div class="billto">
            <strong>BILL TO</strong><br>
            {{ $history->billed_to_name ?? ($tenant->owner_name ?? $tenant->name ?? 'Tenant Account') }}<br>
            {{ $history->billed_to_email ?? ($tenant->email ?? '') }}<br>
            {{ $history->billed_to_address ?? ($tenant->street . ', ' . $tenant->city . ', ' . $tenant->province) }}
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Quantity</th>
                    <th>Description</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $quantity }}</td>
                    <td>
                        {{ $history->description ?? 'DCMS Pro subscription' }}<br>
                        <small>Coverage Date: {{ \Illuminate\Support\Carbon::parse($invoiceDate)->format('Y-m-d') }}</small>
                    </td>
                    <td class="right">&#8369; {{ number_format((float) $rate, 2) }}</td>
                    <td class="right">&#8369; {{ number_format((float) $subtotal, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="right">&#8369; {{ number_format((float) $subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Tax</td>
                <td class="right">&#8369; {{ number_format((float) $tax, 2) }}</td>
            </tr>
            <tr>
                <td class="label grand">Invoice Total</td>
                <td class="right grand">&#8369; {{ number_format((float) $total, 2) }}</td>
            </tr>
        </table>

        <div class="note"><strong>Applied Transaction:</strong> {{ $appliedTransaction }}</div>
    </div>
</body>
</html>
