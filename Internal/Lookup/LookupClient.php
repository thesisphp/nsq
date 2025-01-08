<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Lookup;

use Amp\Future;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\HttpStatus;
use function Amp\async;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 * @psalm-import-type LookupRawInfo from LookupResponse
 */
final class LookupClient
{
    /** @var positive-int */
    private const DEFAULT_RETRY_LIMIT = 5;

    private readonly HttpClient $httpClient;

    /**
     * @param non-empty-list<non-empty-string> $hosts
     */
    public function __construct(
        private readonly array $hosts,
        ?HttpClient $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?: (new HttpClientBuilder())
            ->retry(self::DEFAULT_RETRY_LIMIT)
            ->build();
    }

    /**
     * @param non-empty-string $topic
     * @throws UnableToLookup
     * @throws TopicNotFound
     */
    public function lookup(string $topic): LookupResult
    {
        $futures = [];
        foreach ($this->hosts as $host) {
            $futures[] = async(fn(): LookupResponse => $this->doLookup($host, $topic));
        }

        /** @var array<non-empty-string, bool> $channels */
        $channels = [];
        /** @var array<non-empty-string, ProducerInfo> $producers */
        $producers = [];

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
     * @param non-empty-string $topic
     * @throws UnableToLookup
     * @throws TopicNotFound
     */
    private function doLookup(string $host, string $topic): LookupResponse
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
            throw new TopicNotFound();
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
