<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyQ - Real-Time Queue & Booking SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 50% 50%, #111827 0%, #030712 100%);
        }
        .glow-btn {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
            transition: all 0.3s ease;
        }
        .glow-btn:hover {
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.7);
        }
    </style>
</head>
<body class="text-white min-h-screen flex flex-col justify-between overflow-x-hidden">

    <!-- Header -->
    <header class="container mx-auto px-6 py-6 flex justify-between items-center z-10">
        <div class="flex items-center space-x-3">
            <span class="text-2xl font-extrabold tracking-wider bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 bg-clip-text text-transparent">EasyQ</span>
            <span class="text-xs text-gray-500 font-semibold self-end mb-1">by <a href="https://ss-labz.pages.dev/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-400 transition-colors">SS Labz</a></span>
        </div>
        <div>
            <span class="text-sm text-gray-400">Multi-Tenant Queue Management Engine</span>
        </div>
    </header>

    <!-- Hero Area -->
    <main class="container mx-auto px-6 py-12 flex flex-col lg:flex-row items-center justify-between flex-grow z-10">
        <div class="lg:w-1/2 space-y-8 text-center lg:text-left">
            <h1 class="text-5xl lg:text-7xl font-extrabold leading-tight">
                Instantly Upgrade Your <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">Daily Schedule</span>
            </h1>
            <p class="text-lg text-gray-400 max-w-lg mx-auto lg:mx-0">
                Centralized real-time queue dashboards, smart booking validation, and automated notifications. Perfect for Salons, Spas, Clinics, and Repair Shops.
            </p>
            
            <div class="flex flex-wrap gap-4 justify-center lg:justify-start pt-4">
                <div class="flex items-center space-x-2 text-sm text-gray-300">
                    <span class="text-emerald-400">✓</span> <span>Dedicated Tenant Databases</span>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-300">
                    <span class="text-emerald-400">✓</span> <span>Laravel Reverb WebSockets</span>
                </div>
            </div>
        </div>

        <!-- Signup Form -->
        <div class="lg:w-5/12 w-full mt-12 lg:mt-0">
            <div class="bg-gray-900/60 backdrop-blur-xl border border-gray-800 rounded-3xl p-8 lg:p-10 shadow-2xl relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute -top-16 -right-16 w-32 h-32 bg-blue-500/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-16 -left-16 w-32 h-32 bg-indigo-500/20 rounded-full blur-3xl"></div>

                <h3 class="text-2xl font-bold mb-2">Create Your Business Domain</h3>
                <p class="text-gray-400 text-sm mb-6">Launch a dedicated instance for your company in 5 seconds.</p>

                <form id="tenant-form" class="space-y-6">
                    @csrf
                    <div>
                        <label for="company" class="block text-xs uppercase tracking-wider text-gray-400 mb-2 font-semibold">Business Name (Subdomain)</label>
                        <div class="relative flex items-center">
                            <input type="text" id="company" name="company" placeholder="my-business" required
                                class="w-full bg-gray-950/80 border border-gray-850 rounded-xl px-4 py-3 text-white placeholder-gray-650 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-mono">
                            <span class="absolute right-4 text-gray-500 font-semibold font-mono">.localhost</span>
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Only letters, numbers, and dashes are allowed.</p>
                    </div>

                    <div>
                        <label for="email" class="block text-xs uppercase tracking-wider text-gray-400 mb-2 font-semibold">Owner's Email Address</label>
                        <input type="email" id="email" name="email" placeholder="owner@company.com" required
                            class="w-full bg-gray-950/80 border border-gray-850 rounded-xl px-4 py-3 text-white placeholder-gray-650 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>

                    <div id="error-box" class="hidden text-sm bg-red-900/30 border border-red-800 text-red-300 p-4 rounded-xl"></div>
                    <div id="success-box" class="hidden text-sm bg-emerald-900/30 border border-emerald-800 text-emerald-300 p-4 rounded-xl"></div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 rounded-xl transition-all duration-300 glow-btn flex items-center justify-center space-x-2">
                        <span>Provision My App</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-900 py-6 text-center text-xs text-gray-600">
        <p>&copy; 2026 EasyQ SaaS. A product of <a href="https://ss-labz.pages.dev/" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors underline">SS Labz</a>. Powered by Laravel 12 & Reverb.</p>
    </footer>

    <!-- Form Script -->
    <script>
        document.getElementById('tenant-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const errorBox = document.getElementById('error-box');
            const successBox = document.getElementById('success-box');
            
            errorBox.classList.add('hidden');
            successBox.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Provisioning database...';

            try {
                const response = await fetch('/register-tenant', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        company: document.getElementById('company').value,
                        email: document.getElementById('email').value,
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Something went wrong.');
                }

                successBox.innerHTML = `<strong>Success!</strong> Tenant database created. Redirecting you to register your admin account...`;
                successBox.classList.remove('hidden');

                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 2000);

            } catch (err) {
                errorBox.textContent = err.message;
                errorBox.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<span>Provision My App</span> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>`;
            }
        });
    </script>
</body>
</html>
