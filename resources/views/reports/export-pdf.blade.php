<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clinic Report</title>
    <style>
        /* CRITICAL FIX 1: Use consistent margin values */
        @page {
            margin: 80px 24px 60px;  /* Top and Bottom should match header/footer heights */
        }
        
        /* CRITICAL FIX 2: Enable DOMPDF's internal page counter */
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        
        /* FIXED HEADER - Use positive positioning within the margin area */
        header {
            position: fixed;
            top: -68px;  /* Adjusted: (margin-top 80px) - (header height ~66px) = ~14px padding */
            left: 24px;
            right: 24px;
            height: 60px;
            text-align: center;
        }
        
        .logo {
            max-height: 40px;
            width: auto;
            margin-bottom: 2px;
        }
        
        .clinic-name {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            line-height: 1.2;
        }
        
        .report-period {
            margin-top: 2px;
            font-size: 10px;
            color: #374151;
            font-weight: 700;
        }

        .data-status {
            margin-top: 2px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            color: #1f2937;
        }
        
        /* FIXED FOOTER - Positive positioning */
        footer {
            position: fixed;
            bottom: -48px;  /* Adjusted: (margin-bottom 60px) - (footer height ~36px) = ~24px padding */
            left: 24px;
            right: 24px;
            height: 30px;
            font-size: 9px;
            color: #4b5563;
        }
        
        .footer-left {
            float: left;
            width: 60%;
        }
        
        .footer-right {
            float: right;
            width: 40%;
            text-align: right;
        }
        
        /* CRITICAL FIX 3: Main content needs NO top margin/padding */
        .content {
            display: block;
            margin-top: 0;
            padding-top: 0;
        }
        
        /* CRITICAL FIX 4: Page break management */
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 8px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 3px;
        }

        .section-subtitle {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 700;
            color: #374151;
        }

        .no-data-box {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #78350f;
            padding: 8px 10px;
            margin: 0 0 12px;
            font-size: 10.5px;
            font-weight: 700;
        }
        
        /* TABLE BREAKING FIXES - Most important part */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: auto;  /* Allow tables to break */
        }
        
        /* Force thead to repeat on every page */
        thead {
            display: table-header-group;
        }
        
        /* Prevent rows from breaking inside */
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        /* But allow table to break between rows */
        tbody {
            page-break-inside: auto;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
            word-break: break-word;
        }
        
        th {
            background: #eef2f7;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            text-align: left;
        }
        
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .summary-box {
            border: 1px solid #d1d5db;
            background: #f8fafc;
            padding: 6px 10px;
            margin-bottom: 10px;
        }
        
        .summary-item {
            margin: 2px 0;
            font-size: 11px;
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
        <div class="clinic-name">{{ $tenant?->name ?? 'DENTAL NAME' }}</div>
        <div class="report-period">{{ strtoupper($dateRange['label']) }}</div>
        <div class="data-status">Data Status: {{ $hasContent ? 'WITH RECORDS' : 'NO RECORDS' }}</div>
    </header>

    <footer>
        <div class="footer-left">
            Digital Signature: <strong>{{ $digitalSignature }}</strong>
        </div>
        <div class="footer-right">
            <span></span>
        </div>
    </footer>

    <div class="content">
        @if(!$hasContent)
            <div class="no-data-box">
                No records found for this selected filter period. The report structure is still generated with zero totals for reference.
            </div>
        @endif

        <!-- PATIENTS SECTION -->
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
                        <td class="text-right">₱{{ number_format($patient['balance'], 2) }}</td>
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

        <!-- INCOME SECTION -->
        <div class="section">
            <h2 class="section-title">Total Income</h2>
            <div class="summary-box">
                <div class="summary-item">PAID = ₱{{ number_format($income['paid'], 2) }}</div>
                <div class="summary-item">UNPAID BALANCE = ₱{{ number_format($income['unpaid_balance'], 2) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
