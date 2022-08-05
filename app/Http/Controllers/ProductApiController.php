<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    
    public function store(Request $request)
    {
        $data = $request->all();
        
        $result = [];
        if(is_array($data)){
            $product = $data;
            if($product['product_key'] && $product['notes'] && $product['price'] && $product['qty']){
                $result = $this->productRepository->update($product);
            }
            
        }
        return response()->json($result);
    }
}
