<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt — DCMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .page { box-shadow: none !important; border: none !important; max-width: 100% !important; }
            @page { size: A4; margin: 10mm; }
        }

        .page {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 100px;
            font-weight: 900;
            color: rgba(43, 124, 179, 0.04);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            letter-spacing: -2px;
        }
    </style>
</head>
<body>

    {{-- Print / Download Buttons --}}
    <div class="no-print max-w-800px mx-auto mb-4 flex gap-3 justify-end" style="max-width: 800px;">
        <button onclick="window.print()" class="flex items-center gap-2 bg-[#2B7CB3] hover:bg-[#236491] text-white px-5 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Print / Download PDF
        </button>
        <a href="{{ config('app.url') }}" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg font-bold text-sm transition-colors">
            ← Close
        </a>
    </div>

    <div class="page relative">
        <div class="watermark">DCMS</div>

        {{-- ── HEADER ──────────────────────────────────────────── --}}
        <div class="bg-[#1B3A4B] px-8 py-7 relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    {{-- Logo --}}
                    <div class="w-14 h-14 bg-[#2B7CB3] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="font-black text-white text-xl tracking-tight">DC</span>
                    </div>
                    <div>
                        <div class="font-black text-white text-xl tracking-tight">DCMS</div>
                        <div class="text-blue-300 text-xs font-medium">Dental Clinic Management System</div>
                        <div class="text-blue-400 text-[10px] mt-0.5">Official Payment Receipt</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1">Receipt No.</div>
                    <div class="font-black text-white text-lg tracking-widest">#{{ $signature }}</div>
                    <div class="text-blue-300 text-[10px] mt-1">{{ $paidAt }}</div>
                </div>
            </div>
        </div>

        {{-- ── STATUS BADGE ─────────────────────────────────────── --}}
        <div class="bg-emerald-50 border-b border-emerald-100 px-8 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Payment Confirmed</span>
            </div>
            <span class="text-xs font-medium text-emerald-600">Transaction recorded and verified</span>
        </div>

        <div class="px-8 py-7 relative z-10">

            {{-- ── TWO COLUMNS: Billed To + Payment Info ─────── --}}
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Billed To</div>
                    <div class="font-bold text-gray-900 text-base mb-0.5">{{ $registration->first_name }} {{ $registration->last_name }}</div>
                    <div class="text-sm font-semibold text-[#2B7CB3] mb-1">{{ $registration->clinic_name }}</div>
                    <div class="text-xs text-gray-500">{{ $registration->email }}</div>
                    <div class="text-xs text-gray-500">{{ $registration->phone }}</div>
                    <div class="text-xs text-gray-500 mt-1 leading-relaxed">
                        {{ $registration->street }}, {{ $registration->barangay }},<br>
                        {{ $registration->city }}, {{ $registration->province }}
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Payment Details</div>
                    <table class="w-full text-xs">
                        <tr class="mb-1">
                            <td class="text-gray-500 py-1 pr-3 font-medium">Transaction Code</td>
                            <td class="text-gray-900 font-black tracking-widest">#{{ $signature }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500 py-1 pr-3 font-medium">Stripe Session</td>
                            <td class="text-gray-600 font-mono text-[9px] break-all">{{ Str::limit($sessionId, 28) }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500 py-1 pr-3 font-medium">Plan</td>
                            <td class="text-gray-900 font-bold">{{ $plan->name ?? 'Subscription' }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500 py-1 pr-3 font-medium">Billing</td>
                            <td class="text-gray-900 font-bold capitalize">{{ $registration->billing_cycle }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500 py-1 pr-3 font-medium">Duration</td>
                            <td class="text-gray-900 font-bold">{{ $registration->subscription_months ?? 1 }} Month(s)</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500 py-1 pr-3 font-medium">Status</td>
                            <td><span class="bg-yellow-100 text-yellow-700 font-bold text-[10px] px-2 py-0.5 rounded-full">Awaiting Approval</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- ── PLAN FEATURES ────────────────────────────────── --}}
            @if(!empty($features))
            <div class="mb-8">
                <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Subscribed Features</div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="grid grid-cols-2 gap-y-1.5 gap-x-4">
                        @foreach($features as $feature)
                        <div class="flex items-center gap-2 text-xs text-gray-700">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>{{ is_array($feature) ? ($feature['name'] ?? $feature) : $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- ── PAYMENT TOTAL ────────────────────────────────── --}}
            <div class="bg-[#1B3A4B] rounded-xl p-4 flex items-center justify-between mb-8">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1">Total Amount Paid</div>
                    <div class="font-black text-white text-3xl">₱{{ number_format($amountPaid, 2) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-semibold text-blue-300 mb-1">Clinic URL</div>
                    <div class="font-bold text-[#5BC4F5] text-sm">{{ $registration->subdomain }}.{{ parse_url(config('app.url'), PHP_URL_HOST) }}{{ parse_url(config('app.url'), PHP_URL_PORT) ? ':'.parse_url(config('app.url'), PHP_URL_PORT) : '' }}</div>
                    <div class="text-[10px] text-blue-300 mt-1">Available after admin approval</div>
                </div>
            </div>

            {{-- ── WHAT'S NEXT ──────────────────────────────────── --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-8">
                <div class="text-[10px] font-black uppercase tracking-widest text-[#2B7CB3] mb-2">What Happens Next?</div>
                <div class="text-xs text-gray-600 leading-relaxed">
                    Our administrator will review your application and send your login credentials to
                    <strong>{{ $registration->email }}</strong> once approved. This typically takes less than 24 hours.
                </div>
            </div>

            {{-- ── DIGITAL SIGNATURE ────────────────────────────── --}}
            <div class="border-t border-gray-100 pt-6 flex items-end justify-between">
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Digital Signature</div>
                    <div class="font-mono font-black text-gray-700 text-sm tracking-widest border border-gray-200 rounded-lg px-4 py-2 bg-gray-50 inline-block">
                        {{ $signature }}
                    </div>
                    <div class="text-[9px] text-gray-400 mt-1 font-mono">
                        {{ now()->format('Y') }} · {{ now()->format('m/d') }} · {{ substr($signature, 8) }} ms
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Issued By</div>
                    <div class="font-black text-[#1B3A4B] text-sm">DCMS Platform</div>
                    <div class="text-[10px] text-gray-400">Dental Clinic Management System</div>
                    <div class="text-[10px] text-gray-300 mt-1">© {{ now()->format('Y') }} All Rights Reserved</div>
                </div>
            </div>

        </div>

        {{-- ── FOOTER ───────────────────────────────────────────── --}}
        <div class="bg-gray-50 border-t border-gray-100 px-8 py-4 flex items-center justify-between">
            <div class="text-[10px] text-gray-400">This is an official receipt. Please keep it for your records.</div>
            <div class="text-[10px] font-mono text-gray-400">{{ $signature }}</div>
        </div>

    </div>

    <div class="no-print h-6"></div>
</body>
</html>
