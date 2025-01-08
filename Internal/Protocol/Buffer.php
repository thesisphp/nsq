<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

use Typhoon\Endian\endian;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Buffer implements
    WriteBytes,
    ReadBytes,
    \Countable
{
    private string $buffer = '';

    public function __construct(
        private readonly endian $endian = endian::network,
    ) {}

    public function writeUint32(int $v): self
    {
        $this->buffer .= $this->endian->packUint32($v);

        return $this;
    }

    public function write(string $v): self
    {
        $this->buffer .= $v;

        return $this;
    }

    public function writeArray(array $values, callable $writeLength): self
    {
        foreach ($values as $value) {
            $writeLength(\strlen($value));
            $this->write($value);
        }

        return $this;
    }

    public function reset(): string
    {
        [$v, $this->buffer] = [$this->buffer, ''];

        return $v;
    }

    public function read(int $n): string
    {
        return $this->consume($n);
    }

    public function readUint16(): int
    {
        return $this->endian->unpackUint16($this->consume(2));
    }

    public function readInt32(): int
    {
        return $this->endian->unpackInt32($this->consume(4));
    }

    public function readUint64(): int
    {
        return $this->endian->unpackUint64($this->consume(8));
    }

    public function count(): int
    {
        return \strlen($this->buffer);
    }

    /**
     * @param positive-int $n
     * @return non-empty-string
     */
    private function consume(int $n): string
    {
        if (\strlen($this->buffer) < $n) {
            throw new \RuntimeException('Buffer is empty.');
        }

        /** @var non-empty-string $v */
        $v = substr($this->buffer, 0, $n);
        $this->buffer = substr($this->buffer, $n);

        return $v;
    }
}
