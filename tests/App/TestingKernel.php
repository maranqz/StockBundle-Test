<?php

namespace maranqz\StockBundle\tests\App;

use App\Kernel;
use maranqz\StockBundle\StockBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class TestingKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        // TODO: Implement registerBundles() method.
        return [
            new StockBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // TODO: Implement registerContainerConfiguration() method.
    }
}
