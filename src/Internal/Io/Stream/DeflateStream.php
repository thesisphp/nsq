<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Io\Stream;

use Amp\Cancellation;
use Thesis\Nsq\Exception\ImpossibleToCompress;
use Thesis\Nsq\Exception\ImpossibleToDecompress;
use Thesis\Nsq\Exception\StreamIsNotReady;
use Thesis\Nsq\Internal\Io\Stream;

/**
 * @internal
 */
final class DeflateStream implements Stream
{
    private readonly \InflateContext $inflate;

    private readonly \DeflateContext $deflate;

    /**
     * @param non-negative-int $level
     */
    public function __construct(
        private readonly Stream $stream,
        int $level,
    ) {
        $this->inflate = inflate_init(ZLIB_ENCODING_RAW, ['level' => $level])
            ?: throw new StreamIsNotReady('Unable to initialize inflate context.');

        $this->deflate = deflate_init(ZLIB_ENCODING_RAW, ['level' => $level])
            ?: throw new StreamIsNotReady('Unable to initialize deflate context.');
    }

    public function write(string $bytes): void
    {
        /** @var false|non-empty-string $bytes */
        $bytes = deflate_add($this->deflate, $bytes, ZLIB_SYNC_FLUSH);
        if ($bytes === false) {
            throw new ImpossibleToCompress('Unable to compress data using deflate context.');
        }

        $this->stream->write($bytes);
    }

    public function read(int $limit, ?Cancellation $cancellation = null): string
    {
        /** @var false|non-empty-string $bytes */
        $bytes = inflate_add($this->inflate, $this->stream->read($limit, $cancellation), ZLIB_SYNC_FLUSH);
        if ($bytes === false) {
            throw new ImpossibleToDecompress('Unable to decompress data using inflate context.');
        }

        return $bytes;
    }

    public function reference(): void
    {
        $this->stream->reference();
    }

    public function unreference(): void
    {
        $this->stream->unreference();
    }

    public function isClosed(): bool
    {
        return $this->stream->isClosed();
    }

    public function close(): void
    {
        $this->stream->close();
    }
}
