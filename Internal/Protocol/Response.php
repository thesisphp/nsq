<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Response implements Frame
{
    /**
     * @param non-empty-string $body
     */
    public function __construct(
        public readonly string $body,
    ) {}

    /**
     * @param non-empty-string $body
     */
    public static function parse(string $body): Frame
    {
        return match ($body) {
            'OK' => Ok::frame,
            'CLOSE_WAIT' => CloseWait::frame,
            '_heartbeat_' => Heartbeat::frame,
            default => new self($body),
        };
    }
}
