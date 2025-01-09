<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Composer\InstalledVersions;

/**
 * @api
 */
final class ProducerConfig
{
    private const DEFAULT_CONNECTION_TIMEOUT = 10;
    private const DEFAULT_DEFLATE_LEVEL = 6;

    public readonly string $clientId;

    public readonly string $hostname;

    /** @var non-empty-string */
    public readonly string $userAgent;

    /**
     * @param non-empty-string $host
     * @param ?non-empty-string $authenticationSecret
     * @param float $connectionTimeout in seconds
     */
    public function __construct(
        public readonly string $host,
        public readonly ?string $authenticationSecret = null,
        public readonly bool $tcpNodelay = false,
        public readonly float $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT,
        public readonly bool $tls = false,
        public readonly bool $snappy = false,
        public readonly bool $deflate = false,
        public readonly int $deflateLevel = self::DEFAULT_DEFLATE_LEVEL,
    ) {
        $this->hostname = gethostname();
        $this->clientId = explode('.', $this->hostname)[0] ?? '';
        $this->userAgent = 'thesis/nsq@' . (InstalledVersions::getPrettyVersion('thesis/nsq') ?? 'dev');
    }
}
