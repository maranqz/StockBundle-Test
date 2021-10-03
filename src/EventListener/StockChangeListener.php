<?php

namespace maranqz\StockBundle\EventListener;

use maranqz\StockBundle\Event\StockEvent;
use maranqz\StockBundle\Message\StockOutNotification;
use maranqz\StockBundle\StockEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class StockChangeListener implements EventSubscriberInterface
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public static function getSubscribedEvents()
    {
        return [
            StockEvents::CREATE_STOCK => 'onUpdateStock',
            StockEvents::UPDATE_STOCK => 'onUpdateStock',
        ];
    }

    public function onUpdateStock(StockEvent $event)
    {
        $stock = $event->getStock();
        $originData = $event->getOriginData();

        if (isset($originData['count']) && $stock->getCount() === $originData['count']) {
            return;
        }

        $this->bus->dispatch(new StockOutNotification($stock->getSku(), $stock->getBranch()));
    }
}
