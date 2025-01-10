<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Io;

use Amp\Pipeline;
use Revolt\EventLoop;
use Typhoon\Nsq\Config;
use Typhoon\Nsq\Exception\ConnectionWasClosed;
use Typhoon\Nsq\Internal\Protocol;
use Typhoon\Nsq\Internal\Queue;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Connection
{
    /** @var Pipeline\ConcurrentIterator<Protocol\Frame> */
    private readonly Pipeline\ConcurrentIterator $iterator;

    /** @var Pipeline\Queue<Protocol\Frame> */
    private readonly Pipeline\Queue $queue;

    /** @var Queue\Deq<Protocol\Command> */
    private readonly Queue\Deq $commands;

    private readonly StreamConnector $connector;

    private ?Stream $stream = null;

    private ?Protocol\Writer $writer = null;

    private bool $running = false;

    public function __construct(Config $config)
    {
        $this->connector = new StreamConnector($config);

        /** @var Pipeline\Queue<Protocol\Frame> $queue */
        $queue = new Pipeline\Queue();
        $this->queue = $queue;
        $this->iterator = $queue->iterate();

        /** @var Queue\Deq<Protocol\Command> $commands */
        $commands = new Queue\Deq();
        $this->commands = $commands;
    }

    public function send(Protocol\Command $command): void
    {
        if ($this->writer === null) {
            $this->commands->push($command);
        }

        if (!$this->running) {
            $this->run();
        }

        $this->stream?->reference();

        try {
            $this->writer?->write($command);
        } catch (ConnectionWasClosed) {
            $this->close();
        }
    }

    public function receive(): ?Protocol\Frame
    {
        return $this->iterator->continue()
            ? $this->iterator->getValue()
            : null;
    }

    public function close(): void
    {
        $this->running = false;
        $this->stream?->close();
        $this->writer = null;
        $this->stream = null;
    }

    private function run(): void
    {
        $connector = &$this->connector;
        $stream = &$this->stream;
        $writer = &$this->writer;
        $queue = &$this->queue;
        $commands = &$this->commands;
        $running = &$this->running;

        EventLoop::queue(static function () use (
            &$connector,
            &$stream,
            &$writer,
            &$queue,
            &$commands,
            &$running,
        ): void {
            while ($running) {
                try {
                    if ($stream === null) {
                        [$stream, $writer, $reader] = $connector->connect();
                    }

                    $reader ??= new Protocol\Reader($stream);
                    $writer ??= new Protocol\Writer($stream);

                    $commands->iter($writer->write(...));

                    while ($stream !== null && !$stream->isClosed()) {
                        try {
                            $queue->push($reader->read());
                            $stream->unreference();
                        } catch (ConnectionWasClosed) {
                            $stream = null;
                            $writer = null;
                        } catch (\Throwable $e) {
                            $queue->error($e);
                        }
                    }
                } catch (\Throwable $e) {
                    $queue->error($e);
                    $running = false;
                }
            }

            if (!$queue->isComplete()) {
                $queue->complete();
            }
        });

        $this->running = true;
    }
}
