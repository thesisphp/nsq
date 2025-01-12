<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

/**
 * @api
 * @psalm-type Consume = callable(Message): void
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

    public function __invoke(Message $message): void
    {
        ($this->callback)($message);
    }
}
