<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Pipeline;
use Revolt\EventLoop;
use Typhoon\Nsq\Channel;
use Typhoon\Nsq\Config;
use Typhoon\Nsq\Internal\Protocol;
use Typhoon\Nsq\Topic;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Client
{
    private readonly Connection $connection;

    /** @var Queue<Completion> */
    private readonly Queue $completionQueue;

    /** @var Pipeline\ConcurrentIterator<Protocol\Message> */
    private readonly Pipeline\ConcurrentIterator $messages;

    public function __construct(Config $config)
    {
        $this->connection = $connection = new Connection($config);

        /** @var Queue<Completion> $completionQueue */
        $completionQueue = new Queue();
        $this->completionQueue = $completionQueue;

        /** @var Pipeline\Queue<Protocol\Message> $messages */
        $messages = new Pipeline\Queue();
        $this->messages = $messages->iterate();

        EventLoop::queue(static function () use ($completionQueue, $connection, $messages): void {
            try {
                while (($response = $connection->receive()) !== null) {
                    if ($response instanceof Protocol\Heartbeat) {
                        $connection->send(Protocol\Command::nop());
                    } elseif ($response instanceof Protocol\Message) {
                        $messages->push($response);
                    } else {
                        $completionQueue->next(static function (Completion $completion) use ($response): void {
                            $completion->complete($response);
                        });
                    }
                }
            } catch (\Throwable $e) {
                $completionQueue->iter(static function (Completion $completion) use ($e): void {
                    $completion->error($e);
                });
            } finally {
                $messages->complete();
            }
        });
    }

    /**
     * @param non-empty-string $message
     * @throws \Throwable
     */
    public function pub(Topic $topic, string $message, ?Cancellation $cancellation = null): void
    {
        $this
            ->queue(Protocol\Command::pub($topic, $message))
            ->await($cancellation);
    }

    /**
     * @throws \Throwable
     */
    public function sub(Topic $topic, Channel $channel, ?Cancellation $cancellation = null): void
    {
        $this
            ->queue(Protocol\Command::sub($topic, $channel))
            ->await($cancellation);
    }

    /**
     * @param non-negative-int $count
     * @throws \Throwable
     */
    public function rdy(int $count): void
    {
        $this->queue(Protocol\Command::rdy($count), wait: false);
    }

    /**
     * @param non-empty-string $id
     * @throws \Throwable
     */
    public function fin(string $id): void
    {
        $this->queue(Protocol\Command::fin($id), wait: false);
    }

    /**
     * @param non-empty-string $message
     * @param non-negative-int $delay
     * @throws \Throwable
     */
    public function dpub(
        Topic $topic,
        string $message,
        int $delay,
        ?Cancellation $cancellation = null,
    ): void {
        $this
            ->queue(Protocol\Command::dpub($topic, $message, $delay))
            ->await($cancellation);
    }

    /**
     * @param non-empty-list<non-empty-string> $messages
     * @throws \Throwable
     */
    public function mpub(
        Topic $topic,
        array $messages,
        ?Cancellation $cancellation = null,
    ): void {
        $this
            ->queue(Protocol\Command::mpub($topic, $messages))
            ->await($cancellation);
    }

    public function close(): void
    {
        $this->connection->close();
    }

    public function receive(): ?Protocol\Message
    {
        return $this->messages->continue()
            ? $this->messages->getValue()
            : null;
    }

    /**
     * @template T
     * @param Protocol\Command<T> $command
     * @psalm-return ($wait is true ? Future<T> : null)
     * @throws \Throwable
     */
    private function queue(Protocol\Command $command, bool $wait = true): ?Future
    {
        $deferred = null;
        if ($wait) {
            /** @var DeferredFuture<T> $deferred */
            $deferred = new DeferredFuture();
            $this->completionQueue->push(new Completion($command, $deferred));
        }

        try {
            $this->connection->send($command);
        } catch (\Throwable $e) {
            $this->connection->close();

            throw $e;
        }

        return $deferred?->getFuture();
    }
}
