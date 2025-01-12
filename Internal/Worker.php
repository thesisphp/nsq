<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal;

use Revolt\EventLoop;
use Typhoon\Nsq\Channel;
use Typhoon\Nsq\Config;
use Typhoon\Nsq\Consumer;
use Typhoon\Nsq\Message;
use Typhoon\Nsq\Topic;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Worker
{
    private const STATE_CLOSED = 0;
    private const STATE_OPEN = 1;

    /** @var positive-int in seconds */
    private const LOOKUP_INTERVAL = 15;

    /** @var self::* */
    private int $state = self::STATE_CLOSED;

    /** @var array<non-empty-string, Client> */
    private array $clients = [];

    private ?string $lookupReferenceId = null;

    /**
     * TODO Consider having ConsumerSupervisor create clients, which will avoid DDOS of nsqlookupd servers when many workers are running at once?
     */
    public function __construct(
        private readonly Lookup\LookupClient $lookupClient,
        private readonly Config $config,
        private readonly Topic $topic,
        private readonly Channel $channel,
        private readonly Consumer $consumer,
    ) {}

    public function run(): void
    {
        EventLoop::queue($this->lookup(...));

        $this->lookupReferenceId = EventLoop::repeat(
            self::LOOKUP_INTERVAL,
            $this->lookup(...),
        );

        $this->state = self::STATE_OPEN;
    }

    public function stop(): void
    {
        if ($this->state === self::STATE_CLOSED) {
            return;
        }

        foreach ($this->clients as $client) {
            $client->close();
        }

        if ($this->lookupReferenceId !== null) {
            EventLoop::unreference($this->lookupReferenceId);
            $this->lookupReferenceId = null;
        }

        $this->state = self::STATE_CLOSED;
    }

    public function __destruct()
    {
        $this->stop();
    }

    private function lookup(): void
    {
        $result = $this->lookupClient->lookup($this->topic);

        foreach ($result->producers as $producer) {
            if (!isset($this->clients[$producer->connectionDsn()])) {
                $client = new Client(
                    $producer->connectionDsn(),
                    $this->config,
                );

                $this->clients[$producer->connectionDsn()] = $client;
                $this->consumeClient($client);
            }
        }
    }

    private function consumeClient(Client $client): void
    {
        $topic = $this->topic;
        $channel = $this->channel;
        $consumer = $this->consumer;

        EventLoop::queue(static function () use ($client, $topic, $channel, $consumer): void {
            $client->sub($topic, $channel);
            $client->rdy($consumer->rdy);

            $rdy = $consumer->rdy;

            while (($message = $client->receive()) !== null) {
                $consumer(new Message(
                    fin: $client->fin(...),
                    touch: $client->touch(...),
                    requeue: $client->requeue(...),
                    id: $message->id,
                    body: $message->body,
                    timestamp: $message->timestamp,
                    attempts: $message->attempts,
                ));

                if (--$rdy === 0) {
                    $client->rdy($consumer->rdy);
                    $rdy = $consumer->rdy;
                }
            }
        });
    }
}
