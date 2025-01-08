<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

use Typhoon\Nsq\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Writer
{
    private readonly Buffer $buffer;

    public function __construct(
        private readonly Io\Stream $stream,
    ) {
        $this->buffer = new Buffer();
    }

    /**
     * @throws \Throwable
     */
    public function write(Command $command): void
    {
        $command->write($this->buffer);

        if (($bytes = $this->buffer->reset()) !== '') {
            $this->stream->write($bytes);
        }
    }
}
