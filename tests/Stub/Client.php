<?php

declare(strict_types=1);

namespace Thesis\Nsq\Stub;

/**
 * @internal
 * @psalm-internal Thesis\Nsq
 */
interface Client
{
    /**
     * @param non-empty-string $id
     */
    public function fin(string $id): void;

    /**
     * @param non-empty-string $id
     */
    public function touch(string $id): void;

    /**
     * @param non-empty-string $id
     * @param non-negative-int $timeout
     */
    public function requeue(string $id, int $timeout): void;
}
