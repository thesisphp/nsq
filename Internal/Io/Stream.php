<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Typhoon\Nsq\Internal\Protocol\Frame;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
interface Stream
{
    /**
     * @param non-empty-string $bytes
     * @throws \Throwable
     */
    public function write(string $bytes): void;

    public function receive(): ?Frame;

    public function reference(): void;

    public function unreference(): void;

    public function close(): void;
}
