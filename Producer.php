<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Amp\Cancellation;
use Amp\Future;
use Typhoon\Nsq\Internal\Io\NsqConnection;
use Typhoon\Nsq\Internal\Protocol;
use Amp\Socket;

/**
 * @api
 */
final class Producer
{
    private ?NsqConnection $connection = null;

    public function __construct(
        private readonly ProducerConfig $config,
    ) {}

    /**
     * @param non-empty-string $topic
     * @param non-empty-string $message
     * @throws \Throwable
     */
    public function pub(string $topic, string $message, ?Cancellation $cancellation = null): void
    {
        $this
            ->request(Protocol\Command::pub($topic, $message), $cancellation)
            ->await($cancellation);
    }

    /**
     * @param non-empty-string $topic
     * @param non-empty-string $message
     * @param non-negative-int $delay
     * @throws \Throwable
     */
    public function dpub(
        string $topic,
        string $message,
        int $delay,
        ?Cancellation $cancellation = null,
    ): void {
        $this
            ->request(Protocol\Command::dpub($topic, $message, $delay), $cancellation)
            ->await($cancellation);
    }

    /**
     * @param non-empty-string $topic
     * @param non-empty-list<non-empty-string> $messages
     * @throws \Throwable
     */
    public function mpub(
        string $topic,
        array $messages,
        ?Cancellation $cancellation = null,
    ): void {
        $this
            ->request(Protocol\Command::mpub($topic, $messages), $cancellation)
            ->await($cancellation);
    }

    /**
     * @throws \Throwable
     */
    public function close(?Cancellation $cancellation = null): void
    {
        $this->connection?->close($cancellation);
    }

    /**
     * @template T
     * @param Protocol\Command<T> $command
     * @return Future<T>
     * @throws \Throwable
     */
    private function request(Protocol\Command $command, ?Cancellation $cancellation = null): Future
    {
        return $this->connection()->request($command, $cancellation);
    }

    /**
     * @throws \Throwable
     */
    private function connection(): NsqConnection
    {
        if ($this->connection === null) {
            $context = (new Socket\ConnectContext())
                ->withConnectTimeout($this->config->connectionTimeout);

            if ($this->config->tcpNodelay) {
                $context = $context->withTcpNoDelay();
            }

            $this->connection = new NsqConnection(
                Socket\connect($this->config->host, $context),
            );

            $this->connection->open(new Protocol\Negotiate(
                clientId: $this->config->clientId,
                hostname: $this->config->hostname,
                authenticationSecret: $this->config->authenticationSecret,
                tlsv1: $this->config->tls,
                deflate: $this->config->deflate,
                deflateLevel: $this->config->deflateLevel,
                snappy: $this->config->snappy,
                userAgent: $this->config->userAgent,
            ));
        }

        return $this->connection;
    }
}
