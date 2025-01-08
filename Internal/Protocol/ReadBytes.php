<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
interface ReadBytes
{
    /**
     * @param positive-int $n
     * @return non-empty-string
     */
    public function read(int $n): string;

    /**
     * @return non-negative-int
     */
    public function readUint16(): int;

    public function readInt32(): int;

    /**
     * @return non-negative-int
     */
    public function readUint64(): int;
}
