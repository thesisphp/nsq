<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Message
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $body
     * @param non-negative-int $attempts
     * @param non-negative-int $timestamp
     */
    public function __construct(
        public readonly int $timestamp,
        public readonly int $attempts,
        public readonly string $id,
        public readonly string $body,
    ) {}
}
