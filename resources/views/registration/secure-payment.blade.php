<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment — DCMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .bg-landing iframe {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            border: none; z-index: -10; pointer-events: none;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-100 min-h-screen relative font-sans">

    {{-- Landing page as background (fixed and blurred) --}}
    <iframe src="/" class="fixed inset-0 w-full h-full border-0" style="z-index:-10; pointer-events:none;" tabindex="-1" aria-hidden="true"></iframe>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" style="z-index:-5;"></div>

    {{-- Document flow container to allow natural scrolling and clicking --}}
    <div class="relative z-10 w-full min-h-screen py-10 px-4 sm:px-6 flex justify-center">
        {{-- .my-auto acts like items-center but doesn't cut off top content if height exceeds screen --}}
        <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl flex flex-col lg:flex-row my-auto overflow-hidden">

            {{-- ── LEFT: Order Summary ─────────────────────────── --}}
            <div class="bg-[#1B3A4B] text-white w-full lg:w-80 flex-shrink-0 p-8 flex flex-col">
                {{-- Logo --}}
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-10 h-10 bg-[#2B7CB3] rounded-lg flex items-center justify-center font-black text-white text-lg">DC</div>
                    <div>
                        <div class="font-black text-white text-sm tracking-widest uppercase">DCMS</div>
                        <div class="text-blue-300 text-[10px] font-medium">Dental Clinic Management</div>
                    </div>
                </div>

                <div class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-2">Order Summary</div>

                {{-- Clinic --}}
                <div class="mb-6">
                    <div class="text-[10px] font-semibold text-blue-300 uppercase tracking-widest mb-1">Clinic</div>
                    <div class="font-bold text-white text-base">{{ $order['clinic_name'] ?? 'Your Clinic' }}</div>
                </div>

                {{-- Plan --}}
                <div class="mb-4">
                    <div class="text-[10px] font-semibold text-blue-300 uppercase tracking-widest mb-1">Plan</div>
                    <div class="font-bold text-white">{{ $order['plan_name'] ?? 'Subscription' }}</div>
                </div>

                {{-- Duration --}}
                <div class="mb-4">
                    <div class="text-[10px] font-semibold text-blue-300 uppercase tracking-widest mb-1">Duration</div>
                    @if($order['billing_cycle'] === 'yearly')
                        <div class="font-bold text-white">Annual (12 Months)</div>
                        <div class="text-[10px] text-emerald-400 font-bold mt-1">🎉 Best Value!</div>
                    @else
                        <div class="font-bold text-white">{{ $order['months'] }} {{ $order['months'] == 1 ? 'Month' : 'Months' }}</div>
                    @endif
                </div>

                <div class="border-t border-white/20 my-4"></div>

                {{-- Total --}}
                <div class="mb-2">
                    <div class="text-[10px] font-semibold text-blue-300 uppercase tracking-widest mb-1">Total Due Today</div>
                    <div class="font-black text-3xl text-white">₱{{ number_format($order['amount'] ?? 0, 2) }}</div>
                </div>

                <div class="mt-auto pt-10">
                    <div class="flex items-center gap-2 text-[10px] text-blue-300 font-medium mb-2">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                        SSL Secured
                    </div>
                    <div class="flex items-center gap-2 text-[10px] text-blue-300 font-medium mb-2">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                        PCI Compliant
                    </div>
                    <div class="text-[10px] text-blue-400 mt-4">Powered by <span class="font-bold text-white">Stripe</span></div>
                </div>
            </div>

            {{-- ── RIGHT: Stripe Checkout ──────────────────────── --}}
            <div class="flex-1 bg-white min-h-[600px] flex flex-col">
                <div class="p-6 border-b border-gray-100 flex-shrink-0">
                    <h1 class="text-lg font-black text-gray-900 tracking-tight">Secure Checkout</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Complete your payment securely below</p>
                </div>
                {{-- Let Stripe embedded dictate its own interior height/scroll, while extending down --}}
                <div class="p-2 flex-1" id="stripe-embedded-checkout"></div>
            </div>

        </div>
    </div>

    <script>
        const clientSecret = @json($clientSecret);
        const sessionId    = @json($sessionId);
        const successBase  = @json($successUrl);

        async function initStripe() {
            const stripe = Stripe(@json($stripeKey));
            const checkout = await stripe.initEmbeddedCheckout({
                clientSecret,
                onComplete() {
                    window.location.href = successBase + '?session_id=' + sessionId;
                },
            });
            checkout.mount('#stripe-embedded-checkout');
        }
        initStripe();
    </script>
</body>
</html>
