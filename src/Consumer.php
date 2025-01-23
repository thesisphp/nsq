<?php

declare(strict_types=1);

namespace Thesis\Nsq;

/**
 * @api
 * @phpstan-type Consume = callable(Delivery): void
 */
final class Consumer
{
    /** @var Consume */
    private $callback;

    /**
     * @param Consume $callback
     * @param positive-int $rdy
     */
    public function __construct(
        callable $callback,
        public readonly int $rdy = 1,
    ) {
        $this->callback = $callback;
    }

    public function __invoke(Delivery $message): void
    {
        ($this->callback)($message);
    }
}
