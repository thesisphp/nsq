<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Io;

use Amp\Cancellation;
use Thesis\Nsq\Exception\ConnectionWasClosed;

/**
 * @internal
 */
interface Stream
{
    /**
     * @param non-empty-string $bytes
     * @throws ConnectionWasClosed
     */
    public function write(string $bytes): void;

    /**
     * @param positive-int $limit
     * @return non-empty-string
     * @throws ConnectionWasClosed
     */
    public function read(int $limit, ?Cancellation $cancellation = null): string;

    public function reference(): void;

    public function unreference(): void;

    public function isClosed(): bool;

    public function close(): void;
}
