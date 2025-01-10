<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Amp\Cancellation;

/**
 * @api
 */
final class Producer
{
    private readonly Internal\Client $client;

    public function __construct(Config $config)
    {
        $this->client = new Internal\Client($config);
    }

    /**
     * @param non-empty-string|Topic $topic
     * @param non-empty-string $message
     * @throws \Throwable
     */
    public function pub(
        string|Topic $topic,
        string $message,
        ?Cancellation $cancellation = null,
    ): void {
        $this->client->pub(Topic::create($topic), $message, $cancellation);
    }

    /**
     * @param non-empty-string|Topic $topic
     * @param non-empty-string $message
     * @param non-negative-int $delay in milliseconds
     * @throws \Throwable
     */
    public function dpub(
        string|Topic $topic,
        string $message,
        int $delay,
        ?Cancellation $cancellation = null,
    ): void {
        $this->client->dpub(Topic::create($topic), $message, $delay, $cancellation);
    }

    /**
     * @param non-empty-string|Topic $topic
     * @param non-empty-list<non-empty-string> $messages
     * @throws \Throwable
     */
    public function mpub(
        string|Topic $topic,
        array $messages,
        ?Cancellation $cancellation = null,
    ): void {
        $this->client->mpub(Topic::create($topic), $messages, $cancellation);
    }

    public function close(): void
    {
        $this->client->close();
    }
}
