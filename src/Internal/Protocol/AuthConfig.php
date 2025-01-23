<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 * @phpstan-type AuthRawConfig = array{
 *     identity: non-empty-string,
 *     identity_url: non-empty-string,
 *     permission_count: non-negative-int,
 * }
 */
final class AuthConfig
{
    /**
     * @param non-empty-string $identity
     * @param non-empty-string $identityUrl
     * @param non-negative-int $permissionCount
     */
    public function __construct(
        public readonly string $identity,
        public readonly string $identityUrl,
        public readonly int $permissionCount,
    ) {}

    /**
     * @param AuthRawConfig $options
     */
    public static function fromArray(array $options): self
    {
        return new self(
            identity: $options['identity'],
            identityUrl: $options['identity_url'],
            permissionCount: $options['permission_count'],
        );
    }

    /**
     * @param non-empty-string $json
     * @throws \JsonException
     */
    public static function fromJSON(string $json): self
    {
        /** @var AuthRawConfig $options */
        $options = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return self::fromArray($options);
    }
}
