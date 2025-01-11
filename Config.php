<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Composer\InstalledVersions;

/**
 * @api
 */
final class Config
{
    private const DEFAULT_CONNECTION_TIMEOUT = 10;
    private const DEFAULT_DEFLATE_LEVEL = 6;
    private const PACKAGE_NAME = 'thesis/nsq';

    public readonly string $clientId;

    public readonly string $hostname;

    /** @var non-empty-string */
    public readonly string $userAgent;

    public readonly bool $deflate;

    public readonly bool $snappy;

    /**
     * @param ?non-empty-string $authenticationSecret
     * @param float $connectionTimeout in seconds
     * @param int<1, 9> $deflateLevel
     */
    public function __construct(
        public readonly ?string $authenticationSecret = null,
        public readonly bool $tcpNodelay = false,
        public readonly float $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT,
        public readonly bool $tls = false,
        ?bool $snappy = null,
        ?bool $deflate = null,
        public readonly int $deflateLevel = self::DEFAULT_DEFLATE_LEVEL,
    ) {
        $this->hostname = gethostname();
        $this->clientId = explode('.', $this->hostname)[0] ?? '';
        $this->userAgent = \sprintf('%s@%s', self::PACKAGE_NAME, (InstalledVersions::isInstalled(self::PACKAGE_NAME) ? InstalledVersions::getPrettyVersion(self::PACKAGE_NAME) : 'dev') ?? 'dev');

        $deflate ??= $zlibLoaded = \extension_loaded('zlib');
        if ($deflate && ($zlibLoaded ?? \extension_loaded('zlib')) === false) {
            throw Exception\ExtensionNotAvailable::forZlib();
        }

        $this->deflate = $deflate;

        $snappy ??= $snappyLoaded = \extension_loaded('snappy');
        if ($snappy && ($snappyLoaded ?? \extension_loaded('snappy')) === false) {
            throw Exception\ExtensionNotAvailable::forSnappy();
        }

        $this->snappy = $snappy;
    }
}
