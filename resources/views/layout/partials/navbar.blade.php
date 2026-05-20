<!-- Professional Header -->
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">

            <!-- Logo -->
            <div class="flex items-center gap-4">
                <div class="bg-primary p-2 rounded-xl shadow-md">
                    <img src="{{ asset('delta_favicon.png') }}"
                        alt="DSA Logo"
                        class="h-10 w-10 object-contain">
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-wide">
                        DSA
                    </h1>
                    <p class="text-sm text-gray-500">
                        Webhook Management System
                    </p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="hidden lg:flex items-center gap-3">

                <a href="{{ url('/customers') }}"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 hover:bg-primary hover:text-white transition duration-300">
                    Clients
                </a>

                <a href="{{ url('/products') }}"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 hover:bg-primary hover:text-white transition duration-300">
                    Products
                </a>

                <a href="{{ url('/orders') }}"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 hover:bg-primary hover:text-white transition duration-300">
                    Orders
                </a>

                <a href="{{ url('/payments') }}"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 hover:bg-primary hover:text-white transition duration-300">
                    Collections
                </a>

            </nav>

            <!-- Mobile Button -->
            <button class="lg:hidden text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-7 w-7"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

        </div>
    </div>
</header>