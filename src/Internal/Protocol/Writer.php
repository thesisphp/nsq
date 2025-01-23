<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

use Thesis\Nsq\Exception\ConnectionWasClosed;
use Thesis\Nsq\Internal\Io;

/**
 * @internal
 */
final class Writer
{
    public function __construct(
        private readonly Io\Stream $stream,
        private readonly Buffer $buffer = new Buffer(),
    ) {}

    public function upgrade(Io\Stream $stream): self
    {
        return new self(
            $stream,
            $this->buffer,
        );
    }

    /**
     * @throws ConnectionWasClosed
     */
    public function write(Command $command): void
    {
        $command->writeTo($this->buffer);

        if (($bytes = $this->buffer->reset()) !== '') {
            $this->stream->reference();
            $this->stream->write($bytes);
        }
    }
}
