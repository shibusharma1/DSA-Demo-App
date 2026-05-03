<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->paginate(10);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_name' => 'required|string|max:255',
            'product_code' => 'nullable|string|max:100',
            'mrp'          => 'nullable|numeric',
            'd_price'      => 'nullable|numeric',
            'r_price'      => 'nullable|numeric',
            'unit_name'    => 'nullable|string|max:100',
            'inventory_available_quantity' => 'nullable|integer',
            'status'       => 'required|string',
        ]);

        Product::create($data);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'product_name' => 'sometimes|required|string|max:255',
            'product_code' => 'nullable|string|max:100',
            'mrp'          => 'nullable|numeric',
            'd_price'      => 'nullable|numeric',
            'r_price'      => 'nullable|numeric',
            'unit_name'    => 'nullable|string|max:100',
            'inventory_available_quantity' => 'nullable|integer',
            'status'       => 'required|string',
        ]);

        $product->update($data);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }
}