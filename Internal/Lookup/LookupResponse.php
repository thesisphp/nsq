<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Lookup;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 * @psalm-import-type ProduceRawInfo from ProducerInfo
 * @psalm-type LookupRawInfo = array{channels?: list<non-empty-string>, producers?: list<ProduceRawInfo>}
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
