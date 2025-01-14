<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

/**
 * @api
 */
final class LookupConfig
{
    private const LOOKUP_INTERVAL = 15;
    private const DEFAULT_LOOKUP_ATTEMPTS = 5;
    private const DEFAULT_LOOKUP_SLEEP_INTERVAL = 0.2;
    private const DEFAULT_LOOKUP_SLEEP_MAX_INTERVAL = 1;
    private const DEFAULT_JITTER = 0.1;

    /**
     * @param non-empty-list<non-empty-string> $hosts
     * @param float $interval in seconds
     * @param non-negative-int $attempts
     * @param float $sleep in seconds
     * @param float $maxSleep in seconds
     * @param float $jitter in seconds
     */
    public function __construct(
        public readonly array $hosts,
        public readonly float $interval = self::LOOKUP_INTERVAL,
        public readonly int $attempts = self::DEFAULT_LOOKUP_ATTEMPTS,
        public readonly float $sleep = self::DEFAULT_LOOKUP_SLEEP_INTERVAL,
        public readonly float $maxSleep = self::DEFAULT_LOOKUP_SLEEP_MAX_INTERVAL,
        public readonly float $jitter = self::DEFAULT_JITTER,
    ) {}
}
