<!-- Header -->
<header class="bg-primary text-white sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">

        <h1 class="text-2xl font-bold flex items-center gap-3">
            <img src="{{ asset('delta_favicon.png') }}" alt="Delta Tech Logo" class="h-10 w-10 bg-white rounded-md p-1">
            <span class="tracking-wide">DSA</span>
        </h1>

        <nav class="g-2">
            <a href="{{ url('/customers') }}"
                class="bg-white text-primary font-semibold mx-2 px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                Client List
            </a>
            <a href="{{ url('/products') }}"
                class="bg-white text-primary font-semibold  mx-2 px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                Products/Item List
            </a>
            
{{-- 
            <a href="{{ url('/clients') }}"
                class="bg-white text-primary font-semibold  mx-2 px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                clients List
            </a> --}}

            <a href="{{ url('/orders') }}"
                class="bg-white text-primary font-semibold  mx-2 px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                Order List(One way sync only DSA -> ERPNEXT)
            </a>
            <a href="{{ url('/payments') }}"
                class="bg-white text-primary font-semibold  mx-2 px-4 py-2 rounded-md shadow hover:bg-gray-100 transition">
                Collection List
            </a>
        </nav>
    </div>
</header>
