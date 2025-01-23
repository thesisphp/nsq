<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Lookup;

/**
 * @internal
 * @phpstan-import-type ProduceRawInfo from ProducerInfo
 * @phpstan-type LookupRawInfo = array{channels?: list<non-empty-string>, producers?: list<ProduceRawInfo>}
 */
final class LookupResponse
{
    /**
     * @param list<non-empty-string> $channels
     * @param list<ProducerInfo> $producers
     */
    public function __construct(
        public readonly array $channels = [],
        public readonly array $producers = [],
    ) {}

    /**
     * @param LookupRawInfo $response
     */
    public static function fromArray(array $response): self
    {
        return new self(
            channels: $response['channels'] ?? [],
            producers: array_map(ProducerInfo::fromArray(...), $response['producers'] ?? []),
        );
    }
}
