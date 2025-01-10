<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io\Stream;

use Amp\ByteStream\ResourceStream;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\Socket\Socket;
use Typhoon\Nsq\Exception\ConnectionWasClosed;
use Typhoon\Nsq\Internal\Io\Stream;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class SocketStream implements Stream
{
    public function __construct(
        private readonly Socket $socket,
    ) {}

    public function write(string $bytes): void
    {
        try {
            $this->socket->write($bytes);
        } catch (StreamException $e) {
            throw new ConnectionWasClosed($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function read(int $limit, ?Cancellation $cancellation = null): string
    {
        $bytes = $this->socket->read(cancellation: $cancellation, limit: $limit);
        if ($bytes === '' || $bytes === null) {
            throw new ConnectionWasClosed();
        }

        return $bytes;
    }

    public function reference(): void
    {
        if ($this->socket instanceof ResourceStream) {
            $this->socket->reference();
        }
    }

    public function unreference(): void
    {
        if ($this->socket instanceof ResourceStream) {
            $this->socket->unreference();
        }
    }

    public function isClosed(): bool
    {
        return $this->socket->isClosed();
    }

    public function close(): void
    {
        if (!$this->isClosed()) {
            $this->socket->close();
        }
    }
}
