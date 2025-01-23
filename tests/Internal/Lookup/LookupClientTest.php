<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Lookup;

use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Nsq\Topic;

#[CoversClass(LookupClient::class)]
final class LookupClientTest extends TestCase
{
    public function testTopicNotFound(): void
    {
        $request = new Request('http://127.0.0.1:4161/lookup?topic=test');
        $request->setHeaders(['Content-Type' => 'application/octet-stream']);

        $httpclient = $this->createMock(DelegateHttpClient::class);
        $httpclient
            ->expects(self::once())
            ->method('request')
            ->with($request)
            ->willReturn(new Response('1.1', HttpStatus::NOT_FOUND, 'Not Found', [], '', $request));

        $client = new LookupClient(['http://127.0.0.1:4161'], new HttpClient($httpclient, []));
        self::assertEquals(new LookupResult(), $client->lookup(Topic::create('test')));
    }

    public function testTopicFound(): void
    {
        $httpclient = $this->createMock(DelegateHttpClient::class);

        $request1 = new Request('http://127.0.0.1:4161/lookup?topic=test');
        $request1->setHeaders(['Content-Type' => 'application/octet-stream']);

        $request2 = new Request('http://127.0.0.1:4162/lookup?topic=test');
        $request2->setHeaders(['Content-Type' => 'application/octet-stream']);

        $responseBody = '{"channels": ["logs"], "producers": [{"broadcast_address": "6fe9b4814271", "remote_address": "172.24.0.8:51010", "hostname": "6fe9b4814271", "version": "1.3.0", "tcp_port": 4150, "http_port": 4151}]}';

        $matcher = self::exactly(2);

        $httpclient
            ->expects($matcher)
            ->method('request')
            ->willReturnCallback(static function (Request $value) use ($matcher, $request1, $request2): void {
                /** @phpstan-ignore match.unhandled */
                match ($matcher->numberOfInvocations()) {
                    1 =>  self::assertEquals($request1, $value),
                    2 =>  self::assertEquals($request2, $value),
                };
            })
            ->willReturnOnConsecutiveCalls(
                new Response('1.1', HttpStatus::OK, 'OK', [], $responseBody, $request1),
                new Response('1.1', HttpStatus::OK, 'OK', [], $responseBody, $request2),
            );

        $client = new LookupClient(['http://127.0.0.1:4161', 'http://127.0.0.1:4162'], new HttpClient($httpclient, []));
        $result = $client->lookup(Topic::create('test'));
        self::assertEquals(['logs'], $result->channels);
        self::assertCount(1, $result->producers);
        self::assertEquals(
            new ProducerInfo(
                broadcastAddress: '6fe9b4814271',
                remoteAddress: '172.24.0.8:51010',
                hostname: '6fe9b4814271',
                version: '1.3.0',
                tcpPort: 4150,
                httpPort: 4151,
            ),
            $result->producers[0],
        );
    }
}
