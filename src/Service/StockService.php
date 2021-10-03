<?php

namespace maranqz\StockBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use maranqz\StockBundle\Component\EventDispatcher\DelayedEventDispatcher;
use maranqz\StockBundle\Entity\Stock;
use maranqz\StockBundle\Event\StockEvent;
use maranqz\StockBundle\Exception\StockError;
use maranqz\StockBundle\StockEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

class StockService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EntityManagerInterface $em, DelayedEventDispatcher $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function create(FormInterface $form, bool $withFlush = true): Stock
    {
        $stock = $this->getStock($form);

        $this->em->persist($stock);
        $this->dispatcher->dispatch(
            new StockEvent($stock, []),
            StockEvents::CREATE_STOCK
        );

        $this->flush($withFlush);

        return $stock;
    }

    public function update(FormInterface $form, bool $withFlush = true): Stock
    {
        $stock = $this->getStock($form);

        $originData = $this->em->getUnitOfWork()->getOriginalEntityData($stock);

        $this->dispatcher->dispatch(
            new StockEvent($stock, $originData),
            StockEvents::UPDATE_STOCK
        );

        $this->flush($withFlush);

        return $stock;
    }

    private function getStock(FormInterface $form): Stock
    {
        if (!$form->isValid()) {
            throw new StockError($form);
        }

        $stock = $form->getData();
        assert($stock instanceof Stock);

        return $stock;
    }

    public function flush($withFlush = true)
    {
        if ($withFlush) {
            $this->em->transactional(function () {
                $this->em->flush();
                $this->dispatcher->flush();
            });
        }
    }
}
