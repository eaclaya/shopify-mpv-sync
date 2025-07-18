<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupabaseApiRequest;
use App\Http\Requests\SupabaseBashApiRequest;
use App\Jobs\SentApiShopifyGraphQL;
use App\Jobs\SentApiSupabase;
use App\Repositories\SupabaseRepository;
use Illuminate\Http\Request;

// use Illuminate\Support\Facades\Log;

class SupabaseApiController extends Controller
{
    protected $repository;
    public function __construct(SupabaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function store(SupabaseApiRequest $request)
    {
        $data = $request->getTableData();
        $tableName = $request->getTableName();

        $result = [];
        if (is_array($data)) {
            try {
                if ($data['product_key'] && $data['notes'] && $data['price']) {
                    if (!isset($data['supabase_id'])) {
                        $result = $this->repository->create($tableName, $data);
                    } else {
                        $result = $data;
                    }
                    if (isset($result['supabase_id'])) {
                        // dispatch((new SentApiSupabase($result, $tableName))->delay(15));
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Error fetching products: Data no es Array'], 500);
        }
        return response()->json($result);
    }

    public function update(SupabaseBashApiRequest $request)
    {
        $data = $request->getTableData();
        $tableName = $request->getTableName();
        $level = $request->getLevel() ?? null;

        try {
            if (is_array($data)) {
                $products = $data['products'];
                $level = isset($data['level']) ? $data['level'] : 0;
                $count = 1;
                $time = 90;
                $rowsQty = 25;

                foreach ($products as $product) {
                    $delay = $time * $count + ($level * $rowsQty * $time);
                    if ($product['product_key'] && $product['notes'] && $product['price']) {
                        // dispatch((new SentApiSupabase($product, $tableName))->delay($delay));
                        $count++;
                    }
                }
                return response()->json(['success' => 'Products have been updated successfully'], 200);
            }
            return response()->json(['error' => 'Error fetching products'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

}
