<?php

namespace App\Http\Controllers;

use App\Facades\Shopify;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    public function index()
    {
        $products = $this->productRepository->all();
        return view('products.index', compact('products'));
    }

    public function update(Request $request, Product $product){
        $product = $this->productRepository->update($product);
        $request->session()->flash('success', 'Product updated successfully');
        return redirect()->route('products.index');
    }
}
