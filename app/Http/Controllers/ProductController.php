<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withCount('transactions', 'monthlySalesRecaps')->orderBy('id')->get();
        return view('products.index', compact('products'));
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $product->update($validated);
        return back()->with('success', "Data produk {$product->code} berhasil diperbarui.");
    }
}

