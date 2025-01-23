<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Lookup;

/**
 * @internal
 * @phpstan-type ProduceRawInfo = array{
 *     broadcast_address: non-empty-string,
 *     remote_address: non-empty-string,
 *     hostname: non-empty-string,
 *     version: non-empty-string,
 *     tcp_port: positive-int,
 *     http_port: positive-int,
 * }
 */
final class ProducerInfo implements \Stringable
{
    /**
     * @param non-empty-string $broadcastAddress
     * @param non-empty-string $remoteAddress
     * @param non-empty-string $hostname
     * @param non-empty-string $version
     * @param positive-int $tcpPort
     * @param positive-int $httpPort
     */
    public function __construct(
        public readonly string $broadcastAddress,
        public readonly string $remoteAddress,
        public readonly string $hostname,
        public readonly string $version,
        public readonly int $tcpPort,
        public readonly int $httpPort,
    ) {}

    /**
     * @param ProduceRawInfo $info
     */
    public static function fromArray(array $info): self
    {
        return new self(
            broadcastAddress: $info['broadcast_address'],
            remoteAddress: $info['remote_address'],
            hostname: $info['hostname'],
            version: $info['version'],
            tcpPort: $info['tcp_port'],
            httpPort: $info['http_port'],
        );
    }

    /**
     * @return non-empty-string
     */
    public function connectionDsn(): string
    {
        return "tcp://{$this->broadcastAddress}:{$this->tcpPort}";
    }

    /**
     * @return non-empty-string an unique hash key for producer info
     */
    public function __toString(): string
    {
        return $this->broadcastAddress . $this->hostname . $this->version . $this->tcpPort . $this->httpPort;
    }
}
