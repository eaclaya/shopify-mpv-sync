<?php

namespace App\Repositories;

use App\Facades\Supabase;

class SupabaseRepository
{
    public function getAll(string $tableName)
    {
        return Supabase::getAll($tableName);
    }

    public function findBy(string $tableName, string $column, string $id)
    {
        return Supabase::findBy($tableName, $column, $id);
    }

    public function create(string $tableName, array $data)
    {
        return Supabase::create($tableName, $data);
    }

    public function update(string $tableName, string $id, array $data)
    {
        return Supabase::update($tableName, $id, $data);
    }

    public function delete(string $tableName, string $id)
    {
        return Supabase::delete($tableName, $id);
    }
}
