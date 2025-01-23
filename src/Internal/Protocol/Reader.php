<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

use Amp\Cancellation;
use Thesis\Nsq\Exception\ConnectionWasClosed;
use Thesis\Nsq\Exception\NsqError;
use Thesis\Nsq\Exception\UnexpectedFrame;
use Thesis\Nsq\Internal\Io;

/**
 * @internal
 */
final class Reader
{
    /** @var positive-int */
    private const BODY_LENGTH = 4;

    /** @var positive-int */
    private const FRAME_TYPE = 4;

    /** @var positive-int */
    private const MESSAGE_ID_LENGTH = 16;

    /** @var int timestamp + attempts + message id */
    private const MESSAGE_HEADER_LENGTH = 8 + 2 + self::MESSAGE_ID_LENGTH;

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
     * @throws NsqError
     */
    public function readOk(?Cancellation $cancellation = null): Ok|Response|Message|CloseWait
    {
        /** @var Ok|Response|Error|Message|CloseWait $frame */
        $frame = $this->read($cancellation);
        if ($frame instanceof Error) {
            throw NsqError::fromError($frame);
        }

        return $frame;
    }

    /**
     * @throws ConnectionWasClosed
     */
    public function read(?Cancellation $cancellation = null): Frame
    {
        $this->buffer->write($this->stream->read(self::BODY_LENGTH, $cancellation));

        if (($size = $this->buffer->readInt32()) > 0) {
            $this->buffer->write($this->stream->read($size, $cancellation));
        }

        $type = FrameType::tryFrom($type = $this->buffer->readInt32()) ?: throw UnexpectedFrame::forType($type);

        $bodySize = match ($type) {
            FrameType::Response, FrameType::Error => $size - self::FRAME_TYPE,
            FrameType::Message => $size - self::MESSAGE_HEADER_LENGTH - self::FRAME_TYPE,
        };

        \assert($bodySize > 0, 'body size should be positive.');

        return match ($type) {
            FrameType::Response => Response::parse($this->buffer->read($bodySize)),
            FrameType::Error => Error::parse($this->buffer->read($bodySize)),
            FrameType::Message => new Message(
                timestamp: $this->buffer->readUint64(),
                attempts: $this->buffer->readUint16(),
                id: $this->buffer->read(self::MESSAGE_ID_LENGTH),
                body: $this->buffer->read($bodySize),
            ),
        };
    }
}
