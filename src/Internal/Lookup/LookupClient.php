<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Lookup;

use Amp\Future;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\HttpStatus;
use Thesis\Nsq\Topic;
use function Amp\async;

/**
 * @internal
 * @phpstan-import-type LookupRawInfo from LookupResponse
 */
final class LookupClient
{
    /**
     * @param non-empty-list<non-empty-string> $hosts
     */
    public function __construct(
        private readonly array $hosts,
        private readonly HttpClient $httpClient,
    ) {}

    /**
     * @param list<Topic> $topics
     * @return \Traversable<Topic, LookupResult>
     * @throws UnableToLookup
     * @throws TopicNotFound
     */
    public function lookupAny(array $topics): \Traversable
    {
        foreach ($topics as $topic) {
            yield $topic => $this->lookup($topic);
        }
    }

    /**
     * @throws UnableToLookup
     * @throws TopicNotFound
     */
    public function lookup(Topic $topic): LookupResult
    {
        $futures = [];
        foreach ($this->hosts as $host) {
            $futures[] = async(fn(): LookupResponse => $this->doLookup($host, $topic));
        }

        /** @var array<non-empty-string, bool> $channels */
        $channels = [];
        /** @var array<non-empty-string, ProducerInfo> $producers */
        $producers = [];

        /** @var array<array-key, LookupResponse> $responses */
        $responses = Future\await($futures);

        foreach ($responses as $response) {
            foreach ($response->channels as $channel) {
                $channels[$channel] = true;
            }

            foreach ($response->producers as $producer) {
                $producers[(string) $producer] = $producer;
            }
        }

        return new LookupResult(
            array_keys($channels),
            array_values($producers),
        );
    }

    /**
     * @param non-empty-string $host
     * @throws UnableToLookup
     * @throws TopicNotFound
     */
    private function doLookup(string $host, Topic $topic): LookupResponse
    {
        $request = new Request("{$host}/lookup?topic={$topic}");
        $request->setHeaders([
            'Content-Type' => 'application/octet-stream',
        ]);

        try {
            $response = $this->httpClient->request($request);
        } catch (HttpException $e) {
            throw UnableToLookup::dueToHTTPError($host, $topic, $e->getMessage(), $e);
        }

        if ($response->getStatus() === HttpStatus::NOT_FOUND) {
            return new LookupResponse();
        }

        if ($response->getStatus() >= HttpStatus::BAD_REQUEST) {
            throw UnableToLookup::dueToHTTPError($host, $topic, HttpStatus::getReason($response->getStatus()));
        }

        try {
            /** @var LookupRawInfo $lookupRawInfo */
            $lookupRawInfo = json_decode($response->getBody()->buffer(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw UnableToLookup::dueToBadResponse($host, $topic, $e);
        } catch (\Throwable $e) {
            throw UnableToLookup::dueToHTTPError($host, $topic, $e->getMessage(), $e);
        }

        return LookupResponse::fromArray($lookupRawInfo);
    }
}
