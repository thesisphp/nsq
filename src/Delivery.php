<?php

declare(strict_types=1);

namespace Thesis\Nsq;

/**
 * @api
 * @phpstan-type Fin = callable(non-empty-string): void
 * @phpstan-type Touch = callable(non-empty-string): void
 * @phpstan-type Requeue = callable(non-empty-string, non-negative-int): void
 */
final class Delivery
{
    /** @var Fin */
    private $fin;

    /** @var Touch */
    private $touch;

    /** @var Requeue */
    private $requeue;

    /**
     * @param Fin $fin
     * @param Touch $touch
     * @param Requeue $requeue
     * @param non-empty-string $id
     * @param non-empty-string $body
     * @param non-negative-int $timestamp
     * @param non-negative-int $attempts
     */
    public function __construct(
        callable $fin,
        callable $touch,
        callable $requeue,
        public readonly string $id,
        public readonly string $body,
        public readonly int $timestamp,
        public readonly int $attempts,
        private Internal\DeliveryState $state = Internal\DeliveryState::Received,
    ) {
        $this->fin = $fin;
        $this->touch = $touch;
        $this->requeue = $requeue;
    }

    public function fin(): void
    {
        $this->proceed(
            fn() => ($this->fin)($this->id),
            Internal\DeliveryState::Finished,
        );
    }

    public function touch(): void
    {
        $this->proceed(
            fn() => ($this->touch)($this->id),
            Internal\DeliveryState::Touched,
        );
    }

    /**
     * @param non-negative-int $timeout in milliseconds
     */
    public function requeue(int $timeout): void
    {
        $this->proceed(
            fn() => ($this->requeue)($this->id, $timeout),
            Internal\DeliveryState::Requeued,
        );
    }

    /**
     * @param callable(): void $do
     */
    private function proceed(callable $do, Internal\DeliveryState $to): void
    {
        if ($this->state->completed()) {
            throw Exception\MessageWasProcessed::fromState($this->state);
        }

        $this->state = $to;
        $do();
    }
}
