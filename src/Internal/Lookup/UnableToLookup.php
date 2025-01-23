<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Lookup;

use Thesis\Nsq\Topic;

/**
 * @internal
 */
final class UnableToLookup extends \Exception
{
    /**
     * @param non-empty-string $host
     */
    public static function dueToHTTPError(
        string $host,
        Topic $topic,
        string $reason,
        ?\Throwable $e = null,
    ): self {
        return new self("Unable to lookup '{$topic}' at host '{$host}' due to http error '{$reason}'.", previous: $e);
    }

    /**
     * @param non-empty-string $host
     */
    public static function dueToBadResponse(
        string $host,
        Topic $topic,
        \Throwable $e,
    ): self {
        return new self("Unable to lookup '{$topic}' at host '{$host}' due to bad response '{$e->getMessage()}'.", previous: $e);
    }
}
