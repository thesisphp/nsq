<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Lookup;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class LookupResult
{
    /**
     * @param list<non-empty-string> $channels
     * @param list<ProducerInfo> $producers
     */
    public function __construct(
        public readonly array $channels = [],
        public readonly array $producers = [],
    ) {}
}
