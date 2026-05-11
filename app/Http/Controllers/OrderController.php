<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')->latest()->paginate(10);
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $customers = Customer::orderBy('id','desc')->get();
        return view('orders.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'order_note' => 'nullable',
        ]);

        $order = Order::create($data);

        $total = 0;

        foreach ($request->items as $item) {
            $amount = $item['rate'] * $item['quantity'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $item['product_name'],
                'rate' => $item['rate'],
                'quantity' => $item['quantity'],
                'amount' => $amount,
            ]);

            $total += $amount;
        }

        $order->update([
            'tot_amount' => $total,
            'grand_total' => $total,
            'due_amount' => $total,
        ]);

        return redirect()->route('orders.index')
            ->with('success', 'Order created successfully.');
    }

    public function edit(Order $order)
    {
        $order->load('items');
        $customers = Customer::orderBy('id','desc')->get();

        return view('orders.edit', compact('order', 'customers'));
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->only('customer_id','order_date','due_date','order_note'));

        $order->items()->delete();

        $total = 0;

        foreach ($request->items as $item) {
            $amount = $item['rate'] * $item['quantity'];

            $order->items()->create([
                'product_name' => $item['product_name'],
                'rate' => $item['rate'],
                'quantity' => $item['quantity'],
                'amount' => $amount,
            ]);

            $total += $amount;
        }

        $order->update([
            'tot_amount' => $total,
            'grand_total' => $total,
            'due_amount' => $total,
        ]);

        return redirect()->route('orders.index')
            ->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('success', 'Order deleted.');
    }
}