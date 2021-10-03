<?php

namespace maranqz\StockBundle\Event;

use maranqz\StockBundle\Entity\Stock;
use Symfony\Contracts\EventDispatcher\Event;

class StockEvent extends Event
{
    /**
     * @var Stock
     */
    private $stock;
    /**
     * @var array
     */
    private $originData;

    public function __construct(Stock $stock, array $originData)
    {
        $this->stock = $stock;
        $this->originData = $originData;
    }

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function getOriginData(): array
    {
        return $this->originData;
    }
}
