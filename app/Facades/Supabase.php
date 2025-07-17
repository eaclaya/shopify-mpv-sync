<?php

namespace App\Facades;

class Supabase
{
    protected static function resolveFacade($name)
    {
        return app('Supabase');
    }
    public static function __callStatic($name, $arguments)
    {
        return static::resolveFacade($name)->$name(...$arguments);
    }
}
