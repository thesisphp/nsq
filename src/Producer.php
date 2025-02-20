<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use Amp\Cancellation;

/**
 * @api
 */
final class Producer
{
    private readonly Internal\Client $client;

    /**
     * @param non-empty-string $host
     */
    public function __construct(
        string $host,
        Config $config = new Config(),
    ) {
        $this->client = new Internal\Client($host, $config);
    }

    /**
     * @param non-empty-string|Topic $topic
     * @param list<Message>|Message $messages
     * @throws \Throwable
     */
    public function publish(
        string|Topic $topic,
        array|Message $messages,
        ?Cancellation $cancellation = null,
    ): void {
        if (!\is_array($messages)) {
            $messages = [$messages];
        }

        if (\count($messages) === 0) {
            return;
        }

        if (\count($messages) > 1) {
            $this->mpub($topic, array_map(static fn(Message $message): string => $message->body, $messages), $cancellation);
        } elseif ($messages[0]->delay !== null) {
            $this->dpub($topic, $messages[0]->body, $messages[0]->delay, $cancellation);
        } else {
            $this->pub($topic, $messages[0]->body, $cancellation);
        }
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
