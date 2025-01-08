<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

use Amp\Cancellation;
use Typhoon\ByteOrder\ReadFrom;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
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

    private readonly ReadFrom $reader;

    private readonly Buffer $buffer;

    public function __construct(ReadFrom $reader)
    {
        $this->reader = $reader;
        $this->buffer = new Buffer();
    }

    public function read(?Cancellation $cancellation = null): Frame
    {
        $this->buffer->write($this->reader->read(self::BODY_LENGTH, $cancellation));

        if (($size = $this->buffer->readInt32()) > 0) {
            $this->buffer->write($this->reader->read($size, $cancellation));
        }

        $type = FrameType::tryFrom($type = $this->buffer->readInt32()) ?: throw new \RuntimeException("Unexpected frame type '{$type}'.");

        $bodySize = match ($type) {
            FrameType::Response, FrameType::Error => $size - self::FRAME_TYPE,
            FrameType::Message => $size - self::MESSAGE_HEADER_LENGTH - self::FRAME_TYPE,
        };

        \assert($bodySize > 0, 'body size should be positive.');

        return new Frame(match ($type) {
            FrameType::Response => new Response($this->buffer->read($bodySize)),
            FrameType::Error => Error::parse($this->buffer->read($bodySize)),
            FrameType::Message => new Message(
                timestamp: $this->buffer->readUint64(),
                attempts: $this->buffer->readUint16(),
                id: $this->buffer->read(self::MESSAGE_ID_LENGTH),
                body: $this->buffer->read($bodySize),
            ),
        });
    }
}
