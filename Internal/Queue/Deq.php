<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Queue;

/**
 * @template T
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Deq
{
    /** @var \SplQueue<T> */
    private readonly \SplQueue $splQueue;

    public function __construct()
    {
        /** @var \SplQueue<T> $queue */
        $queue = new \SplQueue();
        $this->splQueue = $queue;
    }

    /**
     * @param T $item
     */
    public function push(mixed $item): void
    {
        $this->splQueue->push($item);
    }

    /**
     * @param \Closure(T): void $do
     */
    public function next(\Closure $do): void
    {
        $do($this->splQueue->shift());
    }

    /**
     * @param \Closure(T): void $do
     */
    public function iter(\Closure $do): void
    {
        while (!$this->splQueue->isEmpty()) {
            $do($this->splQueue->shift());
        }
    }
}

