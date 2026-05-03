@extends('layout.app')

@push('title', 'Home')

@section('content')
<!-- resources/views/orders/create.blade.php -->

<form method="POST" action="{{ route('orders.store') }}" class="max-w-4xl mx-auto bg-white p-6 shadow">
@csrf

<select name="client_id" class="w-full border p-2 mb-3">
    @foreach($clients as $client)
        <option value="{{ $client->id }}">{{ $client->name }}</option>
    @endforeach
</select>



<input type="date" name="order_date" class="w-full border p-2 mb-3">

<div id="items">
    <div class="flex gap-2 mb-2">
        <input name="items[0][product_name]" placeholder="Product" class="border p-2">
        <input name="items[0][rate]" placeholder="Rate" class="border p-2">
        <input name="items[0][quantity]" placeholder="Qty" class="border p-2">
    </div>
</div>

<button type="button" onclick="addItem()">+ Add Item</button>

<button class="bg-teal-600 text-white px-4 py-2">Save</button>
</form>

<script>
let i = 1;
function addItem(){
    document.getElementById('items').innerHTML += `
    <div class="flex gap-2 mb-2">
        <input name="items[${i}][product_name]" class="border p-2">
        <input name="items[${i}][rate]" class="border p-2">
        <input name="items[${i}][quantity]" class="border p-2">
    </div>`;
    i++;
}
</script>
@endsection