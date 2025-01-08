<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Amp\ByteStream\ResourceStream;
use Amp\Pipeline;
use Amp\Socket\Socket;
use Revolt\EventLoop;
use Typhoon\AmpBridge\AmpReaderWriter;
use Typhoon\ByteBuffer\BufferedReaderWriter;
use Typhoon\ByteOrder\ReaderWriter;
use Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class SocketStream implements Stream
{
    private readonly Socket $socket;

    /** @var Pipeline\ConcurrentIterator<Protocol\Frame> */
    private readonly Pipeline\ConcurrentIterator $iterator;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;

        /** @var Pipeline\Queue<Protocol\Frame> $queue */
        $queue = new Pipeline\Queue();
        $this->iterator = $queue->iterate();

        EventLoop::queue(static function () use ($socket, $queue): void {
            $reader = new Protocol\Reader(
                new ReaderWriter(
                    new BufferedReaderWriter(
                        new AmpReaderWriter($socket),
                    ),
                ),
            );

            while ($value = $reader->read()) {
                $queue->push($value);
            }

            $queue->complete();
        });
    }

    public function write(string $bytes): void
    {
        $this->socket->write($bytes);
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

    public function close(): void
    {
        if (!$this->socket->isClosed()) {
            $this->socket->close();
        }
    }

    public function receive(): ?Protocol\Frame
    {
        if (!$this->iterator->continue()) {
            return null;
        }

        return $this->iterator->getValue();
    }
}
