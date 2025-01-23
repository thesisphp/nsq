<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Lookup;

/**
 * @internal
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
