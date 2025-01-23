<?php

declare(strict_types=1);

namespace Thesis\Nsq;

/**
 * @api
 */
final class Message
{
    /**
     * @param non-empty-string $body
     * @param ?non-negative-int $delay in milliseconds
     */
    public function __construct(
        public readonly string $body,
        public readonly ?int $delay = null,
    ) {}
}
