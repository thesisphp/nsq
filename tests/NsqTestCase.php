<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use PHPUnit\Framework\TestCase;

abstract class NsqTestCase extends TestCase
{
    /**
     * @return non-empty-list<non-empty-string>
     */
    final protected static function lookupHosts(): array
    {
        $hosts = self::env('THESIS_NSQ_LOOKUP_HOSTS');
        $hosts = array_filter(array_map(\strval(...), explode(',', $hosts)), static fn(string $host): bool => $host !== '');
        if (\count($hosts) === 0) {
            throw new \InvalidArgumentException('Expected at least one valid http host as value for "THESIS_NSQ_LOOKUP_HOSTS".');
        }

        return array_values($hosts);
    }

    /**
     * @return non-empty-string
     */
    final protected function producer0(): string
    {
        return self::env('THESIS_NSQ_PRODUCER0_HOST');
    }

    /**
     * @return non-empty-string
     */
    final protected function producer1(): string
    {
        return self::env('THESIS_NSQ_PRODUCER1_HOST');
    }

    /**
     * @return non-empty-string
     */
    final protected function authenticationSecret(): string
    {
        return self::env('THESIS_NSQ_AUTH_SECRET');
    }

    /**
     * @param non-empty-string $envName
     * @return non-empty-string
     */
    final protected static function env(string $envName): string
    {
        return getenv($envName) ?: throw new \InvalidArgumentException(\sprintf('Invalid "%s" value.', $envName));
    }
}
