<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 */
final class Negotiate implements
    \JsonSerializable,
    \Stringable
{
    private const DEFAULT_MESSAGE_TIMEOUT = 5000;
    private const DEFAULT_HEARTBEAT_INTERVAL = 1000;

    /**
     * @param positive-int $msgTimeout
     */
    public function __construct(
        private readonly string $clientId = '',
        private readonly string $hostname = '',
        public readonly int $heartbeatInterval = self::DEFAULT_HEARTBEAT_INTERVAL,
        public readonly bool $tlsv1 = false,
        public readonly bool $deflate = false,
        public readonly int $deflateLevel = 0,
        public readonly bool $snappy = false,
        public readonly int $sampleRate = 0,
        public readonly string $userAgent = '',
        public readonly int $msgTimeout = self::DEFAULT_MESSAGE_TIMEOUT,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'client_id' => $this->clientId,
            'hostname' => $this->hostname,
            'tls_v1' => $this->tlsv1,
            'deflate' => $this->deflate,
            'deflate_level' => $this->deflateLevel,
            'snappy' => $this->snappy,
            'sample_rate' => $this->sampleRate,
            'user_agent' => $this->userAgent,
            'msg_timeout' => $this->msgTimeout,
            'heartbeat_interval' => $this->heartbeatInterval,
            'feature_negotiation' => true,
        ];
    }

    /**
     * @return non-empty-string
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this, flags: JSON_THROW_ON_ERROR);
    }
}
