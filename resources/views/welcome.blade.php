@extends('layout.app')

@push('title', 'Home')

@section('content')
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-primary via-primary/90 to-indigo-900 text-white">

        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-300 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-28">

            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <!-- Left -->
                <div>

                    <span
                        class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 border border-white/20 text-sm font-medium mb-6">
                        Enterprise Microservice Platform
                    </span>

                    <h1 class="text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                        Modern Laravel
                        <span class="text-blue-200">
                            Webhook Architecture
                        </span>
                    </h1>

                    <p class="text-lg text-gray-200 leading-relaxed mb-8">
                        A scalable Laravel-based microservice ecosystem demonstrating
                        REST API communication, webhook integration,
                        modular backend services, and enterprise-grade architecture.
                    </p>

                    <div class="flex flex-wrap gap-4">

                        <a href="{{ url('/customers') }}"
                            class="bg-white text-primary px-7 py-4 rounded-xl font-semibold shadow-xl hover:scale-105 transition duration-300">
                            Get Started
                        </a>

                        <a href="{{ url('/orders') }}"
                            class="border border-white/30 px-7 py-4 rounded-xl font-semibold hover:bg-white/10 transition duration-300">
                            View Orders
                        </a>

                    </div>

                </div>

                <!-- Right -->
                <div class="relative">

                    <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 shadow-2xl">

                        <div class="space-y-6">

                            <div class="bg-white/10 rounded-2xl p-5">
                                <h3 class="font-semibold text-lg mb-2">REST APIs</h3>
                                <p class="text-gray-200 text-sm">
                                    High-performance API communication between services.
                                </p>
                            </div>

                            <div class="bg-white/10 rounded-2xl p-5">
                                <h3 class="font-semibold text-lg mb-2">Webhook Events</h3>
                                <p class="text-gray-200 text-sm">
                                    Real-time event-driven integrations and notifications.
                                </p>
                            </div>

                            <div class="bg-white/10 rounded-2xl p-5">
                                <h3 class="font-semibold text-lg mb-2">Scalable Backend</h3>
                                <p class="text-gray-200 text-sm">
                                    Modular architecture built for independent scaling.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-gray-50">

        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Core Features
                </h2>

                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Designed for enterprise-grade applications with scalable microservice architecture and real-time
                    integrations.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">

                @php
                    $features = [
                        [
                            'title' => 'Microservice Architecture',
                            'desc' => 'Independent modular services for scalability and maintainability.',
                        ],
                        [
                            'title' => 'REST API Communication',
                            'desc' => 'Seamless communication between services using APIs.',
                        ],
                        [
                            'title' => 'Webhook Integration',
                            'desc' => 'Real-time event-driven architecture and notifications.',
                        ],
                        [
                            'title' => 'Authentication & Security',
                            'desc' => 'Secure APIs with validation and protected endpoints.',
                        ],
                        [
                            'title' => 'Scalable Infrastructure',
                            'desc' => 'Designed for horizontal scaling and high availability.',
                        ],
                        [
                            'title' => 'Clean Codebase',
                            'desc' => 'Reusable modular code structure following best practices.',
                        ],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div
                        class="group bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition duration-500">

                        <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-6">
                            <div class="w-6 h-6 bg-primary rounded-full"></div>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-3">
                            {{ $feature['title'] }}
                        </h3>

                        <p class="text-gray-600 leading-relaxed">
                            {{ $feature['desc'] }}
                        </p>

                    </div>
                @endforeach

            </div>

        </div>

    </section>

    <section class="py-16 bg-gray-50">

        <div class="max-w-4xl mx-auto">

            <h2 class="text-3xl font-bold text-gray-900 mb-8">
                ERPNext Manual Sync
            </h2>

            <div class="grid md:grid-cols-2 gap-6">

                <!-- Customers -->
                <button
                    class="syncBtn px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                    data-endpoint="/sync/customers/start">

                    Sync Customers
                </button>

                <!-- Products -->
                <button
                    class="syncBtn px-6 py-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                    data-endpoint="/sync/products/start">

                    Sync Products
                </button>

                <!-- Payments -->
                <button
                    class="syncBtn px-6 py-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                    data-endpoint="/sync/payments/start">

                    Sync Payments
                </button>

                <!-- Sales Orders -->
                <button
                    class="syncBtn px-6 py-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                    data-endpoint="/sync/sales-orders/start">

                    Sync Sales Orders
                </button>

            </div>

            <!-- Loader -->
            <div id="loading" class="mt-8 hidden items-center gap-3 text-blue-600 font-medium">

                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">

                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>

                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                    </path>

                </svg>

                Processing sync request...
            </div>

            <!-- Messages -->
            <div id="msg" class="mt-6"></div>

        </div>

        <script>
            const loading = document.getElementById('loading');
            const msg = document.getElementById('msg');

            document.querySelectorAll('.syncBtn').forEach(btn => {

                btn.addEventListener('click', async () => {

                    if (btn.disabled) return;

                    const endpoint = btn.dataset.endpoint;
                    const buttonText = btn.innerText;

                    btn.disabled = true;

                    loading.classList.remove('hidden');
                    loading.classList.add('flex');

                    msg.innerHTML = '';

                    try {

                        const res = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            throw new Error('Request failed');
                        }

                        const data = await res.json();

                        if (data.success) {

                            msg.innerHTML = `
                            <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-xl">
                                ${buttonText} job started successfully.
                            </div>
                        `;

                        } else {

                            msg.innerHTML = `
                            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl">
                                Something went wrong.
                            </div>
                        `;
                        }

                    } catch (err) {

                        console.error(err);

                        msg.innerHTML = `
                        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl">
                            Error starting sync job.
                        </div>
                    `;

                    } finally {

                        loading.classList.add('hidden');
                        loading.classList.remove('flex');

                        btn.disabled = false;
                    }
                });

            });
        </script>

    </section>
@endsection
