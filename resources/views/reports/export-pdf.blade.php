<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clinic Report</title>
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
        
        .logo {
            max-height: 30px;
            width: auto;
            margin-bottom: 1px;
        }
        
        .clinic-name {
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

        .data-status {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            color: #1f2937;
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
        }

        .section-subtitle {
            margin: 0 0 5px;
            font-size: 9.5px;
            font-weight: 700;
            color: #374151;
        }

        .no-data-box {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #78350f;
            padding: 6px 8px;
            margin: 0 0 10px;
            font-size: 9px;
            font-weight: 700;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        
        thead {
            display: table-header-group;
        }
        
        tr {
            page-break-inside: avoid;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 3px 5px;
            vertical-align: top;
            font-size: 8px;
        }
        
        th {
            background: #eef2f7;
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
            page-break-inside: avoid;
        }
        
        .summary-item {
            margin: 2px 0;
            font-size: 10px;
            font-weight: 700;
        }
        
        .text-right {
            text-align: right;
        }
        
    </style>
</head>
<body>
    @php
        $hasContent = ($patientTotal ?? 0) > 0
            || ($appointmentTotal ?? 0) > 0
            || (($income['paid'] ?? 0) > 0)
            || (($income['unpaid_balance'] ?? 0) > 0);
    @endphp

    <header>
        @if(!empty($logoSrc))
            <img src="{{ $logoSrc }}" alt="Dental Logo" class="logo">
        @endif
        <div class="clinic-name">{{ $tenant->name ?? 'DENTAL NAME' }}</div>
        <div class="report-period">{{ strtoupper($dateRange['label']) }}</div>
        <div class="data-status">Data Status: {{ $hasContent ? 'WITH RECORDS' : 'NO RECORDS' }}</div>
    </header>

    <footer>
        <div class="footer-left">
            Digital Signature: <strong>{{ $digitalSignature }}</strong>
        </div>
        <div class="footer-right">
            
        </div>
    </footer>

    <div class="content">
        @if(!$hasContent)
            <div class="no-data-box">
                No records found for this selected filter period.
            </div>
        @endif

        <div class="section">
            <h2 class="section-title">Total Patients</h2>
            <p class="section-subtitle">Total Count: {{ $patientTotal }}</p>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 17%;">Name</th>
                        <th style="width: 22%;">Address</th>
                        <th style="width: 13%;">Mobile</th>
                        <th style="width: 12%;">First Visit</th>
                        <th style="width: 12%;">Last Visit</th>
                        <th style="width: 14%;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td>{{ $patient['id'] }}</td>
                        <td>{{ $patient['name'] }}</td>
                        <td>{{ $patient['address'] }}</td>
                        <td>{{ $patient['mobile'] }}</td>
                        <td>{{ $patient['first_visit'] }}</td>
                        <td>{{ $patient['last_visit'] }}</td>
                        <td class="text-right">PHP {{ number_format($patient['balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">No patients found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Total Appointments</h2>
            <p class="section-subtitle">Total Count: {{ $appointmentTotal }}</p>
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Date & Time</th>
                        <th style="width: 18%;">Queue Ref</th>
                        <th style="width: 24%;">Patient</th>
                        <th style="width: 20%;">Service</th>
                        <th style="width: 18%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                    <tr>
                        <td>{{ $appointment['date_time'] }}</td>
                        <td>{{ $appointment['queue_reference'] }}</td>
                        <td>{{ $appointment['patient_name'] }}</td>
                        <td>{{ $appointment['service'] }}</td>
                        <td>{{ $appointment['status'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">No appointments found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Total Income</h2>
            <div class="summary-box">
                <div class="summary-item">PAID = PHP {{ number_format($income['paid'], 2) }}</div>
                <div class="summary-item">UNPAID BALANCE = PHP {{ number_format($income['unpaid_balance'], 2) }}</div>
            </div>
        </div>
    </div>
</body>
</html>