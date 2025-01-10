<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Amp\DeferredFuture;
use Typhoon\Nsq\Exception\NsqError;
use Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Completion
{
    /**
     * @template T
     * @param Protocol\Command<T> $command
     * @param DeferredFuture<T> $deferred
     */
    public function __construct(
        private readonly Protocol\Command $command,
        private readonly DeferredFuture $deferred,
    ) {}

    public function complete(Protocol\Frame $response): void
    {
        if ($response instanceof Protocol\Error) {
            $this->deferred->error(NsqError::fromError($response));
        } elseif ($response instanceof Protocol\Response) {
            try {
                $this->deferred->complete($this->command->parse($response));
            } catch (\Throwable $e) {
                $this->deferred->error($e);
            }
        } else {
            $this->deferred->complete($response);
        }
    }

    public function error(\Throwable $e): void
    {
        $this->deferred->error($e);
    }
}
