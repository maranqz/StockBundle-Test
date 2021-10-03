<?php

namespace maranqz\StockBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class StockBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
