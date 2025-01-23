<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

use Thesis\Endian\endian;

/**
 * @internal
 */
final class Buffer implements \Countable
{
    private string $buffer = '';

    public function __construct(
        private readonly endian $endian = endian::network,
    ) {}

    /**
     * @param non-negative-int $v
     */
    public function writeUint32(int $v): self
    {
        $this->buffer .= $this->endian->packUint32($v);

        return $this;
    }

    /**
     * @param non-empty-string $v
     */
    public function write(string $v): self
    {
        $this->buffer .= $v;

        return $this;
    }

    /**
     * @param non-empty-list<non-empty-string> $values
     * @param callable(non-negative-int): self $writeLength
     */
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

    /**
     * @param positive-int $n
     * @return non-empty-string
     */
    public function read(int $n): string
    {
        return $this->consume($n);
    }

    /**
     * @return non-negative-int
     */
    public function readUint16(): int
    {
        return $this->endian->unpackUint16($this->consume(2));
    }

    public function readInt32(): int
    {
        return $this->endian->unpackInt32($this->consume(4));
    }

    /**
     * @return non-negative-int
     */
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
