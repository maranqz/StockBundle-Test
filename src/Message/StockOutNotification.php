<?php

namespace maranqz\StockBundle\Message;

class StockOutNotification
{
    /**
     * @var string
     */
    private $sku;
    /**
     * @var string
     */
    private $branch;

    public function __construct(string $sku, string $branch)
    {
        $this->sku = $sku;
        $this->branch = $branch;
    }

    public function getSKU()
    {
        return $this->sku;
    }

    public function getBranch()
    {
        return $this->branch;
    }
}
