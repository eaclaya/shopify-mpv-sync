<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

class ChatbotService
{
    public function get($uri)
    {
        return $this->makeHttRequest($uri, [], 'get');
    }

    public function post($uri, $data){
        return $this->makeHttRequest($uri, $data, 'post');
    }

    public function put($uri, $data){
        return $this->makeHttRequest($uri, $data, 'put');
    }

    protected function makeHttRequest($uri, $data = [], $method = 'get'){
        try{
            $response = Http::withHeaders($this->getHeaders())
                        ->$method($this->getEndpoint($uri), $data);
            return $response->json();
        }catch(\Exception $e){
            return ['errors' => [$e->getMessage()]];
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
