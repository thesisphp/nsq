<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use Amp\Http\Client\HttpClientBuilder;
use Revolt\EventLoop;
use Thesis\Nsq\Internal\Lookup;

/**
 * @api
 * @phpstan-import-type Consume from Consumer
 */
final class ConsumerSupervisor
{
    private const STATE_STOPPED = 0;
    private const STATE_RUN = 1;

    /** @var self::* */
    private int $state = self::STATE_STOPPED;

    private readonly Lookup\LookupClient $lookupClient;

    private ?string $lookupReferenceId = null;

    /** @var array<non-empty-string, true> a key is "topic:channel" to have fast access to check that only one worker is registered for each topic channel */
    private array $exactlyOnce = [];

    /** @var array<non-empty-string, Internal\Client> a key is "topic:channel:dsn" to have fast access to clients */
    private array $topicsToClients = [];

    /** @var array<non-empty-string, Topic> */
    private array $topics = [];

    /** @var array<non-empty-string, list<ChannelWorker>> */
    private array $topicsToWorkers = [];

    public function __construct(
        private readonly LookupConfig $lookup,
        private readonly Config $config = new Config(),
    ) {
        $this->lookupClient = new Lookup\LookupClient(
            $this->lookup->hosts,
            (new HttpClientBuilder())
                ->retry(0) // need to use a more controlled retry mechanism with exponential backoff (@see RetryWithBackoff).
                ->intercept(new Lookup\RetryWithBackoff(
                    attempts: $this->lookup->attempts,
                    sleep: $this->lookup->sleep,
                    maxSleep: $this->lookup->maxSleep,
                    jitter: $this->lookup->jitter,
                ))
                ->build(),
        );
    }

    /**
     * @param non-empty-string|Topic $topic
     * @param non-empty-string|Channel $channel
     * @param Consume|Consumer $consumer
     * @throws \Throwable
     */
    public function consume(
        string|Topic $topic,
        string|Channel $channel,
        callable|Consumer $consumer,
    ): void {
        $topic = Topic::create($topic);
        $channel = Channel::create($channel);

        $workerKey = "{$topic}:{$channel}";

        if (isset($this->exactlyOnce[$workerKey])) {
            throw new \LogicException(\sprintf('Channel "%s" for topic "%s" is already registered.', $channel, $topic));
        }

        $this->exactlyOnce[$workerKey] = true;
        $this->topics[$topic->name] = $topic;
        $this->topicsToWorkers[$topic->name][] = new ChannelWorker(
            channel: $channel,
            worker: new Internal\Worker(
                topic: $topic,
                channel: $channel,
                consumer: !$consumer instanceof Consumer ? new Consumer($consumer) : $consumer,
            ),
        );
    }

    public function run(): void
    {
        if ($this->state === self::STATE_RUN) {
            throw new \LogicException('Unable to run: already run.');
        }

        if (\count($this->topicsToWorkers) === 0) {
            throw new \LogicException('Unable to run: no consumers registered.');
        }

        $this->state = self::STATE_RUN;

        EventLoop::queue($this->lookup(...));

        $this->lookupReferenceId = EventLoop::repeat(
            $this->lookup->interval,
            $this->lookup(...),
        );
    }

    public function stop(): void
    {
        if ($this->state === self::STATE_STOPPED) {
            throw new \LogicException('Unable to stop: not yet run or already stopped.');
        }

        $this->state = self::STATE_STOPPED;

        if ($this->lookupReferenceId !== null) {
            EventLoop::unreference($this->lookupReferenceId);
            $this->lookupReferenceId = null;
        }

        foreach ($this->topicsToClients as $client) {
            $client->close();
        }
    }

    public function __destruct()
    {
        if ($this->state === self::STATE_RUN) {
            $this->stop();
        }
    }

    private function lookup(): void
    {
        foreach ($this->lookupClient->lookupAny(array_values($this->topics)) as $topic => $result) {
            foreach ($this->topicsToWorkers[$topic->name] ?? [] as $worker) {
                foreach ($result->producers as $producer) {
                    $clientKey = "{$topic}:{$worker->channel}:{$producer->connectionDsn()}";
                    if (!isset($this->topicsToClients[$clientKey])) {
                        $this->topicsToClients[$clientKey] = $client = new Internal\Client(
                            $producer->connectionDsn(),
                            $this->config,
                        );
                        $worker($client);
                    }
                }
            }
        }
    }
}

/**
 * @internal
 * @psalm-internal Thesis\Nsq
 */
final class ChannelWorker
{
    public function __construct(
        public readonly Channel $channel,
        public readonly Internal\Worker $worker,
    ) {}

    public function __invoke(Internal\Client $client): void
    {
        $this->worker->work($client);
    }
}
