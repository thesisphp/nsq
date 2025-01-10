<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Message implements Frame
{
    /**
     * @param non-negative-int $timestamp
     * @param non-negative-int $attempts
     * @param non-empty-string $id
     * @param non-empty-string $body
     */
    public function __construct(
        public readonly int $timestamp,
        public readonly int $attempts,
        public readonly string $id,
        public readonly string $body,
    ) {}
}
