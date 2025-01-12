<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Amp\Http\Client\HttpClient;
use Revolt\EventLoop;
use Typhoon\Nsq\Internal\Lookup;

/**
 * @api
 * @psalm-import-type Consume from Consumer
 */
final class ConsumerSupervisor
{
    private const STATE_STOPPED = 0;
    private const STATE_RUN = 1;

    /** @var positive-int in seconds */
    private const LOOKUP_INTERVAL = 15;

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

        $this->assertExactlyOnce($topic, $channel);

        if (!$consumer instanceof Consumer) {
            $consumer = new Consumer($consumer);
        }

        $this->topics[$topic->name] = $topic;
        $this->topicsToWorkers[$topic->name][] = new ChannelWorker(
            channel: $channel,
            worker: new Internal\Worker(
                topic: $topic,
                channel: $channel,
                consumer: $consumer,
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
            self::LOOKUP_INTERVAL,
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

    /**
     * @throws \LogicException
     */
    private function assertExactlyOnce(Topic $topic, Channel $channel): void
    {
        $workerKey = "{$topic}:{$channel}";

        if (isset($this->exactlyOnce[$workerKey])) {
            throw new \LogicException(\sprintf('Channel "%s" for topic "%s" is already registered.', $channel, $topic));
        }

        $this->exactlyOnce[$workerKey] = true;
    }
}

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
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
