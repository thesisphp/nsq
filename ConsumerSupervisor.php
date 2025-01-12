<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Amp\Http\Client\HttpClient;
use Typhoon\Nsq\Internal\Lookup;

/**
 * @api
 * @psalm-import-type Consume from Consumer
 */
final class ConsumerSupervisor
{
    private readonly Lookup\LookupClient $lookupClient;

    /** @var array<non-empty-string, Internal\Worker> */
    private array $workers = [];

    /**
     * @param non-empty-list<non-empty-string> $lookupHosts
     */
    public function __construct(
        array $lookupHosts,
        private readonly Config $config,
        ?HttpClient $httpClient = null,
    ) {
        $this->lookupClient = new Lookup\LookupClient(
            $lookupHosts,
            $httpClient,
        );
    }

    /**
     * @param non-empty-string|Topic $topic
     * @param non-empty-string|Channel $channel
     * @param Consumer|Consume $consumer
     * @throws \Throwable
     */
    public function consume(
        string|Topic $topic,
        string|Channel $channel,
        Consumer|callable $consumer,
    ): void {
        $topic = Topic::create($topic);
        $channel = Channel::create($channel);

        $workerKey = "{$topic}:{$channel}";

        if (isset($this->workers[$workerKey])) {
            throw new \LogicException(\sprintf('Channel "%s" for topic "%s" is already registered.', $channel, $topic));
        }

        if (!$consumer instanceof Consumer) {
            $consumer = new Consumer($consumer);
        }

        $this->workers[$workerKey] = new Internal\Worker(
            lookupClient: $this->lookupClient,
            config: $this->config,
            topic: $topic,
            channel: $channel,
            consumer: $consumer,
        );
    }

    public function run(): void
    {
        if (\count($this->workers) === 0) {
            throw new \LogicException('Unable to run: no consumers registered.');
        }

        foreach ($this->workers as $worker) {
            $worker->run();
        }
    }

    public function stop(): void
    {
        foreach ($this->workers as $worker) {
            $worker->stop();
        }
    }
}
