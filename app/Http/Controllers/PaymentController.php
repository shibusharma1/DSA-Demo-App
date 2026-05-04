<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
// use Log;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['client', 'order'])->latest()->paginate(10);
        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $clients = Client::all();
        $orders  = Order::all();

        // Log::info($orders);

        return view('payments.create', compact('clients', 'orders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'         => 'required',
            'order_id'          => 'nullable',
            'payment_received'  => 'required|numeric',
            'payment_method'    => 'nullable|string',
            'payment_date'      => 'required|date',
            'payment_status'    => 'required|string',
        ]);

        Payment::create($data);

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function edit(Payment $payment)
    {
        $clients = Client::all();
        $orders  = Order::all();

        return view('payments.edit', compact('payment', 'clients', 'orders'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'client_id'         => 'required',
            'order_id'          => 'nullable',
            'payment_received'  => 'required|numeric',
            'payment_method'    => 'nullable|string',
            'payment_date'      => 'required|date',
            'payment_status'    => 'required|string',
        ]);

        $payment->update($data);

        return redirect()->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return back()->with('success', 'Payment deleted successfully.');
    }
}
