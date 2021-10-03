<?php

namespace maranqz\StockBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use maranqz\StockBundle\Repository\StockRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=StockRepository::class)
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=12)
     * @Assert\NotBlank
     */
    private $sku;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=3)
     * @Assert\NotBlank
     */
    private $branch;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     * @Assert\NotBlank
     */
    private $count;

    public function __construct(string $sku, string $branch)
    {
        $this->sku = $sku;
        $this->branch = $branch;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }
}
