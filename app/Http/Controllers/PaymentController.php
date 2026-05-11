<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['customer', 'order'])->latest()->paginate(10);
        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $customers = Customer::orderBy('id','desc')->get();

        return view('payments.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'         => 'required',
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
        $customers = Customer::orderBy('id','desc')->get();

        return view('payments.edit', compact('payment', 'customers'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'customer_id'         => 'required',
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
