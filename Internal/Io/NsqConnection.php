<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Socket\Socket;
use Revolt\EventLoop;
use Typhoon\Nsq\Exception;
use Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class NsqConnection
{
    private readonly Stream $stream;

    private readonly Protocol\Writer $writer;

    /** @var \SplQueue<Completion> */
    private readonly \SplQueue $completionQueue;

    private bool $running = true;

    public function __construct(Socket $socket)
    {
        $this->stream = new SocketStream($socket);
        $this->writer = new Protocol\Writer($this->stream);

        /** @var \SplQueue<Completion> $queue */
        $queue = new \SplQueue();
        $this->completionQueue = $queue;

        $this->handleCompletionQueue();
    }

    /**
     * @throws \Throwable
     */
    public function open(Protocol\Negotiate $negotiate, ?Cancellation $cancellation = null): void
    {
        $this->command(Protocol\Command::magic());

        $response = $this
            ->request(Protocol\Command::identify((string) $negotiate))
            ->await($cancellation);

        if ($response->authRequired) {
            if ($negotiate->authenticationSecret === null) {
                throw new Exception\AuthenticationRequired();
            }

            $this
                ->request(Protocol\Command::auth($negotiate->authenticationSecret))
                ->await($cancellation);
        }
    }

    /**
     * @template T
     * @param Protocol\Command<T> $command
     * @return Future<T>
     * @throws \Throwable
     */
    public function request(Protocol\Command $command, ?Cancellation $cancellation = null): Future
    {
        return $this->write($command, cancellation: $cancellation);
    }

    public function command(Protocol\Command $command, ?Cancellation $cancellation = null): void
    {
        $this->write($command, wait: false, cancellation: $cancellation);
    }

    /**
     * @throws \Throwable
     */
    public function close(?Cancellation $cancellation = null): void
    {
        if ($this->running) {
            $this->request(Protocol\Command::cls())->await($cancellation);
            $this->running = false;
            $this->stream->close();
        }
    }

    /**
     * @template T
     * @param Protocol\Command<T> $command
     * @psalm-return ($wait is true ? Future<T> : null)
     * @throws \Throwable
     */
    private function write(Protocol\Command $command, bool $wait = true, ?Cancellation $cancellation = null): ?Future
    {
        $this->stream->reference();

        try {
            $this->writer->write($command);
        } catch (\Throwable $e) {
            $this->close($cancellation);

            throw $e;
        }

        $deferred = null;
        if ($wait) {
            /** @var DeferredFuture<T> $deferred */
            $deferred = new DeferredFuture();
            $this->completionQueue->push(new Completion($command, $deferred));
        }

        return $deferred?->getFuture();
    }

    private function handleCompletionQueue(): void
    {
        $completionQueue = &$this->completionQueue;
        $writer = &$this->writer;
        $stream = &$this->stream;
        $running = &$this->running;

        EventLoop::queue(static function () use (&$completionQueue, &$writer, &$stream, &$running): void {
            try {
                while ($running) {
                    $stream->unreference();

                    while ($response = $stream->receive()) {
                        $completion = $completionQueue->shift();
                        $completion->complete($response);

                        if ($completionQueue->isEmpty()) {
                            $stream->unreference();
                        }
                    }
                }
            } catch (\Throwable $e) {
                while (!$completionQueue->isEmpty()) {
                    $completion = $completionQueue->shift();
                    $completion->error($e);
                }

                $running = false;
            }
        });
    }
}
