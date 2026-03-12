<!DOCTYPE html>
<html lang="en" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title')</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Tailwind & DaisyUI via CDN for these standalone pages -->
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />

        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Plus Jakarta Sans', 'sans-serif'],
                        },
                        animation: {
                            'fade-in': 'fadeIn 0.5s ease-out',
                            'zoom-in': 'zoomIn 0.5s ease-out',
                        },
                        keyframes: {
                            fadeIn: {
                                '0%': { opacity: '0' },
                                '100%': { opacity: '1' },
                            },
                            zoomIn: {
                                '0%': { transform: 'scale(0.95)' },
                                '100%': { transform: 'scale(1)' },
                            },
                        }
                    },
                },
            }
        </script>

        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased min-h-screen bg-slate-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center space-y-8 animate-fade-in animate-zoom-in">
            <div class="relative inline-block w-full">
                <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="relative bg-white rounded-[2.5rem] p-10 shadow-2xl shadow-blue-500/10 border border-white/50 ring-1 ring-black/[0.03]">
                    <div class="mb-6">
                        <div class="w-20 h-20 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6 transform rotate-3 shadow-lg shadow-blue-200/50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-10 h-10 text-blue-600 -rotate-3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h1 class="text-7xl font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-blue-600 to-indigo-700 tracking-tighter mb-2">
                            @yield('code')
                        </h1>
                        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold uppercase tracking-widest mb-4">
                            <span>@yield('title')</span>
                        </div>
                    </div>

                    <p class="text-slate-600 text-lg font-medium leading-relaxed mb-10">
                        @yield('message')
                    </p>

                    <div class="flex flex-col space-y-3">
                        <a href="/" class="btn btn-primary rounded-2xl shadow-xl shadow-blue-500/30 text-white font-bold h-14 normal-case group bg-gradient-to-r from-blue-600 to-indigo-600 border-none">
                            Return to Dashboard
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <button onclick="window.history.back()" class="btn btn-ghost rounded-2xl text-slate-400 hover:text-slate-600 font-semibold h-12 normal-case">
                            Go Back
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-center space-x-4 text-slate-400 text-xs font-semibold tracking-wide uppercase">
                <span>DCMS &bull; Dental Clinic Management</span>
                <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                <span>&copy; {{ date('Y') }}</span>
            </div>
        </div>
    </body>
</html>
