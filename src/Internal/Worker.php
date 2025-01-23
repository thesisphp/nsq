<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal;

use Revolt\EventLoop;
use Thesis\Nsq\Channel;
use Thesis\Nsq\Consumer;
use Thesis\Nsq\Delivery;
use Thesis\Nsq\Topic;

/**
 * @internal
 */
final class Worker
{
    public function __construct(
        private readonly Topic $topic,
        private readonly Channel $channel,
        private readonly Consumer $consumer,
    ) {}

    public function work(Client $client): void
    {
        $topic = $this->topic;
        $channel = $this->channel;
        $consumer = $this->consumer;

        EventLoop::queue(static function () use ($client, $topic, $channel, $consumer): void {
            $client->sub($topic, $channel);
            $client->rdy($consumer->rdy);

            $rdy = $consumer->rdy;

            while (($message = $client->receive()) !== null) {
                $consumer(new Delivery(
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
