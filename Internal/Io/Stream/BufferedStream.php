<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io\Stream;

use Amp\ByteStream\ReadableResourceStream;
use Amp\Cancellation;
use Typhoon\Nsq\Internal\Io\Stream;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class BufferedStream implements Stream
{
    private const DEFAULT_CHUNK_SIZE = ReadableResourceStream::DEFAULT_CHUNK_SIZE;

    private string $buffer = '';

    public function __construct(
        private readonly Stream $stream,
    ) {}

    public function write(string $bytes): void
    {
        $this->stream->write($bytes);
    }

    public function read(int $limit, ?Cancellation $cancellation = null): string
    {
        $n = $limit - \strlen($this->buffer);

        while ($n > 0) {
            $this->buffer .= $v = $this->stream->read(self::DEFAULT_CHUNK_SIZE, $cancellation);
            $n -= \strlen($v);
        }

        /** @var non-empty-string $bytes */
        $bytes = substr($this->buffer, 0, $limit);
        $this->buffer = substr($this->buffer, $limit);

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
