<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Platform Analytics Report</title>
    <style>
        @page {
            margin: 65px 24px 50px;
        }
        
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        
        header {
            position: fixed;
            top: -53px;
            left: 24px;
            right: 24px;
            height: 48px;
            text-align: center;
        }
        
        .title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-period {
            font-size: 9px;
            color: #374151;
            font-weight: 700;
        }

        footer {
            position: fixed;
            bottom: -38px;
            left: 24px;
            right: 24px;
            height: 24px;
            font-size: 7.5px;
            color: #6b7280;
            border-top: 0.5px solid #d1d5db;
            padding-top: 3px;
        }
        
        .footer-left {
            float: left;
        }
        
        .footer-right {
            float: right;
        }
        
        .section {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 5px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 2px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 8px;
        }
        
        th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.2px;
            text-align: left;
        }
        
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .summary-box {
            border: 1px solid #d1d5db;
            background: #f8fafc;
            padding: 5px 8px;
            margin-bottom: 8px;
        }
        
        .summary-item {
            margin: 2px 0;
            font-size: 10px;
            font-weight: 700;
        }
        
        .text-right {
            text-align: right;
        }

        .badge {
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .badge-active { background-color: #dcfce7; color: #166534; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; }
        
    </style>
</head>
<body>
    <header>
        <div class="title">Platform Analytics Report</div>
        <div class="report-period">{{ strtoupper($dateRange['label']) }}</div>
    </header>

    <footer>
        <div class="footer-left">
            Digital Signature: <strong>{{ $digitalSignature }}</strong>
        </div>
        <div class="footer-right">
            Generated on {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </footer>

    <div class="content">
        @php $firstSection = true; @endphp
        @if(isset($reportData['tenants']))
            <div class="section" @if(!$firstSection) style="page-break-before: always;" @endif>
                @php $firstSection = false; @endphp
                <h2 class="section-title">Tenants</h2>
                <div class="summary-box">
                    <div class="summary-item">Total New Tenants: {{ $reportData['tenants']['total'] }}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Domain</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['tenants']['list'] as $tenant)
                        <tr>
                            <td>{{ $tenant['id'] }}</td>
                            <td>{{ $tenant['name'] }}</td>
                            <td>{{ $tenant['domain'] }}</td>
                            <td>{{ $tenant['plan'] }}</td>
                            <td>{{ strtoupper($tenant['status']) }}</td>
                            <td>{{ $tenant['created_at'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">No new tenants found in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($reportData['subscriptions']))
            <div class="section" @if(!$firstSection) style="page-break-before: always;" @endif>
                @php $firstSection = false; @endphp
                <h2 class="section-title">Subscriptions</h2>
                <div class="summary-box">
                    <div class="summary-item">Total New Subscriptions: {{ $reportData['subscriptions']['total'] }}</div>
                    <div class="summary-item">New Revenue: PHP {{ number_format($reportData['subscriptions']['total_revenue'], 2) }}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tenant</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['subscriptions']['list'] as $sub)
                        <tr>
                            <td>{{ $sub['id'] }}</td>
                            <td>{{ $sub['tenant'] }}</td>
                            <td>{{ $sub['plan'] }}</td>
                            <td>{{ strtoupper($sub['status']) }}</td>
                            <td class="text-right">PHP {{ number_format($sub['amount'], 2) }}</td>
                            <td>{{ $sub['created_at'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">No new subscriptions found in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($reportData['features']))
            <div class="section" @if(!$firstSection) style="page-break-before: always;" @endif>
                @php $firstSection = false; @endphp
                <h2 class="section-title">Features</h2>
                <div class="summary-box">
                    <div class="summary-item">Total New Features: {{ $reportData['features']['total'] }}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Key</th>
                            <th>Type</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['features']['list'] as $feature)
                        <tr>
                            <td>{{ $feature['id'] }}</td>
                            <td>{{ $feature['name'] }}</td>
                            <td>{{ $feature['key'] }}</td>
                            <td>{{ strtoupper($feature['type']) }}</td>
                            <td>{{ $feature['created_at'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">No new features found in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($reportData['support']))
            <div class="section" @if(!$firstSection) style="page-break-before: always;" @endif>
                @php $firstSection = false; @endphp
                <h2 class="section-title">Support & Tickets</h2>
                <div class="summary-box">
                    <div class="summary-item">Total New Tickets: {{ $reportData['support']['total'] }}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Tenant ID</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Category</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['support']['list'] as $ticket)
                        <tr>
                            <td>{{ $ticket['id'] }}</td>
                            <td>{{ $ticket['subject'] }}</td>
                            <td>{{ $ticket['tenant'] }}</td>
                            <td>{{ strtoupper($ticket['status']) }}</td>
                            <td>{{ strtoupper($ticket['priority']) }}</td>
                            <td>{{ strtoupper($ticket['category']) }}</td>
                            <td>{{ $ticket['created_at'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">No new support tickets found in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("helvetica", "normal");
            $pdf->page_text(380, 575, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0.4, 0.4, 0.4));
        }
    </script>
</body>
</html>
