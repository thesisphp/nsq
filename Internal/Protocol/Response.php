<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Response
{
    private const OK = 'OK';
    private const CLOSE_WAIT = 'CLOSE_WAIT';
    private const HEARTBEAT = '_heartbeat_';

    /**
     * @param non-empty-string $body
     */
    public function __construct(
        public readonly string $body,
    ) {}
}
