<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected string $baseUrl;
    protected string $anonKey;
    protected string $serviceRoleKey;

    public function __construct()
    {
        $this->baseUrl = config('services.supabase.url');
        $this->anonKey = config('services.supabase.anon_key');
        $this->serviceRoleKey = config('services.supabase.service_role_key');
    }

    protected function makeRequest(bool $useServiceRole = false)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
            'Prefer' => 'return=representation',
        ];

        if ($useServiceRole) {
            $headers['Apikey'] = $this->anonKey;
            $headers['Authorization'] = 'Bearer ' . $this->serviceRoleKey;
        }

        return Http::withHeaders($headers);
    }

    public function getAll(string $table, array $query = []): array
    {
        try {
            $response = $this->makeRequest()->get($this->baseUrl . $table, $query);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error("SupabaseApi Service Error (getAll - $table): " . $e->getMessage());
            return [];
        }
    }

    public function findBy(string $table, string $column, string $value, array $query = []): ?array
    {
        try {
            $url = $this->baseUrl . $table . '?' . $column . '=eq.' . $value;
            $response = $this->makeRequest($table)->get($url, $query);
            $response->throw();
            return $response->json()[0] ?? null;
        } catch (\Exception $e) {
            Log::error("SupabaseApi Service Error (findBy - $table - $column=$value): " . $e->getMessage());
            return null;
        }
    }

    public function create(string $table, array $data, bool $useServiceRole = true): ?array
    {
        try {
            $response = $this->makeRequest($useServiceRole)->post($this->baseUrl . $table, $data);
            $response->throw();
            return $response->json()[0] ?? null;
        } catch (\Exception $e) {
            Log::error("SupabaseApi Service Error (create - $table): " . $e->getMessage());
            return null;
        }
    }

    public function update(string $table, string $id, array $data, bool $useServiceRole = true): ?array
    {
        try {
            $response = $this->makeRequest($table, $useServiceRole)->patch($this->baseUrl . $table . '?id=eq.' . $id, $data);
            $response->throw();
            return $response->json()[0] ?? null;
        } catch (\Exception $e) {
            Log::error("SupabaseApi Service Error (update - $table): " . $e->getMessage());
            return null;
        }
    }

    public function delete(string $table, string $id, bool $useServiceRole = true): bool
    {
        try {
            $response = $this->makeRequest($table, $useServiceRole)->delete($this->baseUrl . $table . '?id=eq.' . $id);
            $response->throw();
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("SupabaseApi Service Error (delete - $table): " . $e->getMessage());
            return false;
        }
    }
}
