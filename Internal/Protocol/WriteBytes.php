<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
interface WriteBytes
{
    /**
     * @param non-negative-int $v
     */
    public function writeUint32(int $v): self;

    /**
     * @param non-empty-list<non-empty-string> $values
     * @param callable(positive-int): void $writeLength
     */
    public function writeArray(array $values, callable $writeLength): self;

    /**
     * @param non-empty-string $v
     */
    public function write(string $v): self;

    public function reset(): string;
}
