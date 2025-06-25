<?php

namespace App\Facades;

class ShopifyGraphQL
{
    protected static function resolveFacade($name)
    {
        return app('ShopifyGraphQL');
    }
    public static function __callStatic($name, $arguments)
    {
        return static::resolveFacade($name)->$name(...$arguments);
    }
}
