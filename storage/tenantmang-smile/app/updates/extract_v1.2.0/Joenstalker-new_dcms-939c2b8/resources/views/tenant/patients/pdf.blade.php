<!DOCTYPE html>
<html>
<head>
    <title>Patient Record: {{ $patient->first_name }} {{ $patient->last_name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #0ea5e9; padding-bottom: 15px; }
        .header h1 { margin: 0; color: #0ea5e9; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0 0; font-size: 14px; font-weight: bold; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; background-color: #f1f5f9; color: #0f172a; padding: 8px 12px; border-left: 4px solid #0ea5e9; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; color: #475569; font-size: 11px; text-transform: uppercase; }
        .photo-container { float: right; width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
        .photo-container img { width: 100%; height: 100%; object-fit: cover; }
        .info-grid { width: 70%; float: left; }
        .info-row { margin-bottom: 8px; }
        .info-label { font-weight: bold; color: #64748b; font-size: 10px; text-transform: uppercase; display: inline-block; width: 100px; }
        .info-value { color: #0f172a; font-size: 13px; font-weight: bold; }
        .clear { clear: both; }
        .footer { text-align: center; font-size: 10px; color: #94a3b8; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Patient Medical Record</h1>
        <p>ID-{{ str_pad($patient->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="section">
        <div class="section-title">Personal Information</div>
        <div>
            @if($patient->photo_path)
            <div class="photo-container">
                <img src="{{ $patient->photo_path }}" alt="Photo">
            </div>
            @endif
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value">{{ $patient->first_name }} {{ $patient->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $patient->email ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $patient->phone ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <span class="info-value">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Gender:</span>
                    <span class="info-value" style="text-transform: capitalize;">{{ $patient->gender ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $patient->address ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="clear"></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Medical History</div>
        <div style="margin-bottom: 15px;">
            <p style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 4px;">Existing Conditions & Allergies</p>
            <p style="margin-top: 0; font-size: 13px;">{{ $patient->medical_history ?? 'None recorded' }}</p>
        </div>
        <div>
            <p style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 4px;">Operation History</p>
            <p style="margin-top: 0; font-size: 13px;">{{ $patient->operation_history ?? 'None recorded' }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Recent Appointments</div>
        @if($patient->appointments->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Reason for Visit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->appointments->sortByDesc('appointment_datetime')->take(10) as $appointment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($appointment->appointment_datetime)->format('M d, Y g:i A') }}</td>
                    <td style="text-transform: uppercase; font-size: 10px; font-weight: bold;">{{ $appointment->status }}</td>
                    <td>{{ $appointment->reason ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #64748b; font-style: italic;">No recorded appointments for this patient.</p>
        @endif
    </div>

    <div class="footer">
        Generated by DCMS on {{ \Carbon\Carbon::now()->format('M d, Y H:i:s') }}
    </div>
</body>
</html>
