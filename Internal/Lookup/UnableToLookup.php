<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Lookup;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class UnableToLookup extends \Exception
{
    /**
     * @param non-empty-string $host
     * @param non-empty-string $topic
     */
    public static function dueToHTTPError(
        string $host,
        string $topic,
        string $reason,
        ?\Throwable $e = null,
    ): self {
        return new self("Unable to lookup '{$topic}' at host '{$host}' due to http error '{$reason}'.", previous: $e);
    }

    /**
     * @param non-empty-string $host
     * @param non-empty-string $topic
     */
    public static function dueToBadResponse(
        string $host,
        string $topic,
        \Throwable $e,
    ): self {
        return new self("Unable to lookup '{$topic}' at host '{$host}' due to bad response '{$e->getMessage()}'.", previous: $e);
    }
}
