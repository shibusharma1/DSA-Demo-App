@extends('layout.app')

@push('title', 'Home')

@section('content')
    <!-- Hero Section -->
    <section class="flex-1 flex items-center justify-center bg-gradient-to-r from-primary/80 to-primary/60 text-white py-20">
        <div class="text-center px-4 md:px-0 max-w-2xl">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4">Welcome to Our Webhook Service</h2>
            <p class="text-lg md:text-xl mb-6">
                A scalable Laravel microservice architecture demonstrating REST APIs, webhook communication, and modular
                backend services.
            </p>
            <a href="{{ url('/customers') }}"
                class="inline-block bg-white text-primary font-semibold px-6 py-3 rounded-lg shadow-lg hover:bg-gray-100 transition">
                Go to Customer list
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">Key Features</h3>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Microservice Architecture</h4>
                    <p>Independent services with modular design for better scalability and maintainability.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">REST API Communication</h4>
                    <p>Services communicate via REST APIs, enabling seamless integration and API-driven development.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Webhook Integration</h4>
                    <p>Demonstrates webhook communication for event-driven architecture and real-time updates.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Authentication & Security</h4>
                    <p>Secure APIs with authentication and proper validation for data integrity and safety.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Scalable Backend</h4>
                    <p>Design that supports horizontal scaling and independent deployment of services.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Clean Code Structure</h4>
                    <p>Modular codebase with reusable components for efficient development and testing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Architecture Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">Architecture Overview</h3>
            <div class="flex justify-center">
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg max-w-4xl">
                    <pre class="bg-gray-100 p-6 rounded-lg overflow-x-auto text-sm">
                            Client / Frontend
                                    |
                            API Gateway
                                    |
                            ---------------------------------
                            |               |               |
                            User Service   Order Service   Payment Service
                            |               |               |
                            Database       Database        Database
                    </pre>
                    <p class="text-center mt-4 text-gray-700">Each service is independent, scalable, and communicates
                        via APIs or events.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- APIs Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h3 class="text-3xl font-bold text-center mb-12">Available APIs</h3>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white shadow-lg rounded-lg p-6 hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Users API</h4>
                    <p>GET /api/users → List of all users.</p>
                    <p>POST /api/users → Create new user.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Orders API</h4>
                    <p>GET /api/orders → List all orders.</p>
                    <p>POST /api/orders → Create new order.</p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 hover:scale-105 transition">
                    <h4 class="text-xl font-semibold mb-2">Webhook API</h4>
                    <p>POST /api/webhook → Trigger webhook events for real-time communication.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-primary text-white text-center">
        <h3 class="text-3xl font-bold mb-6">Get Started Now</h3>
        <p class="mb-6 max-w-2xl mx-auto">Experience a scalable microservice architecture with Laravel and explore
            modular backend services with webhook integration.</p>
        <a href="/customers"
            class="inline-block bg-white text-primary font-semibold px-6 py-3 rounded-lg shadow-lg hover:bg-gray-100 transition">
            Go to Customer Form
        </a>
    </section>

@endsection
