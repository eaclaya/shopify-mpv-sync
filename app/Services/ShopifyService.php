<?php 
namespace App\Services;
use Illuminate\Support\Facades\Http;

class ShopifyService
{
    public function get($uri)
    {
        try{
            $response = Http::withHeaders($this->getHeaders())
                        ->get($this->getEndpoint($uri));
            return $response->json();   
        }catch(\Exception $e){
            return ['errors' => $e->getMessage()];
        }
    }

    public function post($uri, $data){
        try{
            $response = Http::withHeaders($this->getHeaders())
                        ->post($this->getEndpoint($uri), [
                            'product' => $data
                        ]);
            return $response->json();   
        }catch(\Exception $e){
            return ['errors' => $e->getMessage()];
        }
    }
    protected function getEndpoint($uri)
    {
        $uri = str_replace('.json', '', $uri);
        $host = config('services.shopify.host');
        $version = config('services.shopify.version');
        return "https://{$host}.myshopify.com/admin/api/{$version}/{$uri}.json";
    }

    protected function getHeaders(){
        return [
            'X-Shopify-Access-Token' => config('services.shopify.access_token'),
        ];
    }
}