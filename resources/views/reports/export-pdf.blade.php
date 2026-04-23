<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Clinic Report</title>
    <style>
        @page {
            margin: 100px 25px;
        }
        header {
            position: fixed;
            top: -80px;
            left: 0px;
            right: 0px;
            height: 80px;
            text-align: center;
        }
        footer {
            position: fixed; 
            bottom: -60px; 
            left: 0px; 
            right: 0px;
            height: 50px; 
            font-size: 10px;
            color: #666;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
            text-align: right;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 5px;
        }
        .clinic-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 10px 8px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
            word-wrap: break-word;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .pagenum:before {
            content: counter(page);
        }
        .total-pages:before {
            content: counter(pages);
        }
    </style>
</head>
<body>
    <header>
        @php
            $logoPath = $tenant->logo_path;
            // Handle binary logos from branding service if needed, 
            // but for DOMPDF local paths are better.
        @endphp
        
        @if($logoPath && file_exists(public_path($logoPath)))
            <img src="{{ public_path($logoPath) }}" class="logo">
        @else
            <div style="font-size: 20px; font-weight: bold; color: #3b82f6;">{{ strtoupper($tenant->name) }}</div>
        @endif
        
        <div class="clinic-name">{{ $tenant->name }}</div>
        <div class="report-title">REPORT OF {{ strtoupper($type) }} - {{ $dateRange['label'] }}</div>
    </header>

    <footer>
        <div class="footer-left">
            Page <span class="pagenum"></span>
        </div>
        <div class="footer-right">
            Digital Signature: <strong>{{ $digitalSignature }}</strong><br>
            Generated on: {{ $generatedAt }}
        </div>
    </footer>

    <main>
        @if(count($data) > 0)
            <table>
                <thead>
                    <tr>
                        @foreach(array_keys($data[0]) as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            @foreach($row as $value)
                                <td>{{ $row[$loop->parent->index === 0 ? array_keys($row)[$loop->index] : array_keys($row)[$loop->index]] ?? $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="text-align: center; padding: 50px; color: #999;">
                <h3>No data found for the selected period.</h3>
            </div>
        @endif
    </main>
</body>
</html>
