<?php

namespace maranqz\StockBundle\Component\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

/**
 * Changed from https://github.com/olvlvl/delayed-event-dispatcher.
 */
class DelayedEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var callable
     */
    private $delayArbiter;

    /**
     * @var callable
     */
    private $exceptionHandler;

    /**
     * @var callable
     */
    private $flusher;

    /**
     * @var object[]
     */
    private $queue = [];

    /**
     * @param callable|null $delayArbiter     The delay arbiter determines whether an event should be delayed or not. It's
     *                                        a callable with the following signature: `function($event, string $eventName = null): bool`. The
     *                                        default delay arbiter just returns `true`, all events are delayed. Note: The delay arbiter is only invoked
     *                                        if delaying events is enabled.
     * @param callable|null $exceptionHandler This callable handles exceptions thrown during event dispatching. It's a
     *                                        callable with the following signature:
     *                                        `function(\Throwable $exception, $event, string $eventName = null): void`. The default exception
     *                                        handler just throws the exception.
     * @param callable|null $flusher          By default, delayed events are dispatched with the decorated event dispatcher
     *                                        when flushed, but you can choose another solution entirely, like sending them to consumers using RabbitMQ or
     *                                        Kafka. The callable has the following signature: `function($event, string $eventName = null): void`.
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        bool $disabled = false,
        callable $delayArbiter = null,
        callable $exceptionHandler = null,
        callable $flusher = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->enabled = !$disabled;
        $this->delayArbiter = $delayArbiter ?: function () {
            return true;
        };
        $this->exceptionHandler = $exceptionHandler ?: function (Throwable $exception) {
            throw $exception;
        };
        $this->flusher = $flusher ?: function (object $event, string $eventName = null): object {
            return $this->eventDispatcher->dispatch($event, $eventName);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event, string $eventName = null): object
    {
        if ($this->shouldDelay($event, $eventName)) {
            $this->queue[] = ['event' => $event, 'eventName' => $eventName];

            return $event;
        }

        return $this->eventDispatcher->dispatch($event, $eventName);
    }

    /**
     * Dispatch all the events in the queue.
     *
     * Note: Exceptions raised during dispatching are caught and forwarded to the exception handler defined during
     * construct.
     */
    public function flush()
    {
        while (($item = array_shift($this->queue))) {
            try {
                ($this->flusher)($item['event'], $item['eventName']);
            } catch (Throwable $e) {
                ($this->exceptionHandler)($e, $item['event'], $item['eventName']);
            }
        }
    }

    private function shouldDelay(object $event, string $eventName = null): bool
    {
        return $this->enabled && ($this->delayArbiter)($event, $eventName);
    }
}
