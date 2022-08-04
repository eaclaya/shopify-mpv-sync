<?php
namespace App\Facades;

class Shopify
{
    protected static function resolveFacade($name)
    {
        return app('Shopify');
    }
    public static function __callStatic($name, $arguments)
    {
        return static::resolveFacade($name)->$name(...$arguments);
    }
}