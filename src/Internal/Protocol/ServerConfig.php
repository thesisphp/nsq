<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 * @phpstan-type ServerRawConfig = array{
 *     max_rdy_count: positive-int,
 *     version: non-empty-string,
 *     max_msg_timeout: positive-int,
 *     msg_timeout: positive-int,
 *     tls_v1: bool,
 *     deflate: bool,
 *     deflate_level: int<1, 9>,
 *     max_deflate_level: int<1, 9>,
 *     snappy: bool,
 *     sample_rate: int,
 *     auth_required: bool,
 *     output_buffer_size: int,
 *     output_buffer_timeout: int,
 * }
 */
final class ServerConfig
{
    /**
     * @param positive-int $maxRdyCount
     * @param non-empty-string $version
     * @param positive-int $maxMsgTimeout
     * @param positive-int $msgTimeout
     * @param int<1, 9> $deflateLevel
     * @param int<1, 9> $maxDeflateLevel
     */
    public function __construct(
        public readonly int $maxRdyCount,
        public readonly string $version,
        public readonly int $maxMsgTimeout,
        public readonly int $msgTimeout,
        public readonly bool $tlsv1,
        public readonly bool $deflate,
        public readonly int $deflateLevel,
        public readonly int $maxDeflateLevel,
        public readonly bool $snappy,
        public readonly int $sampleRate,
        public readonly bool $authRequired,
        public readonly int $outputBufferSize,
        public readonly int $outputBufferTimeout,
    ) {}

    /**
     * @param ServerRawConfig $options
     */
    public static function fromArray(array $options): self
    {
        return new self(
            maxRdyCount: $options['max_rdy_count'],
            version: $options['version'],
            maxMsgTimeout: $options['max_msg_timeout'],
            msgTimeout: $options['msg_timeout'],
            tlsv1: $options['tls_v1'],
            deflate: $options['deflate'],
            deflateLevel: $options['deflate_level'],
            maxDeflateLevel: $options['max_deflate_level'],
            snappy: $options['snappy'],
            sampleRate: $options['sample_rate'],
            authRequired: $options['auth_required'],
            outputBufferSize: $options['output_buffer_size'],
            outputBufferTimeout: $options['output_buffer_timeout'],
        );
    }

    /**
     * @param non-empty-string $json
     * @throws \JsonException
     */
    public static function fromJSON(string $json): self
    {
        /** @var ServerRawConfig $options */
        $options = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return self::fromArray($options);
    }
}
