<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use Amp\DeferredFuture;
use PHPUnit\Framework\Attributes\CoversClass;
use Thesis\Nsq\Exception\AuthenticationFailed;
use Thesis\Nsq\Exception\AuthenticationRequired;

#[CoversClass(Producer::class)]
final class ProducerTest extends NsqTestCase
{
    public function testAuthenticationExpected(): void
    {
        $producer = new Producer($this->producer0(), new Config());

        self::expectException(AuthenticationRequired::class);
        $producer->pub('test', 'aaa');
    }

    public function testAuthenticationFailed(): void
    {
        $producer = new Producer($this->producer0(), new Config(
            authenticationSecret: 'invalid',
        ));

        self::expectException(AuthenticationFailed::class);
        $producer->pub('test', 'aaa');
    }

    public function testProduceWithAuthentication(): void
    {
        $config = new Config(authenticationSecret: $this->authenticationSecret());

        $producer = new Producer($this->producer0(), $config);
        $producer->pub('test', 'aaa');
        $producer->close();

        $consumer = new ConsumerSupervisor(new LookupConfig($this->lookupHosts()), $config);

        $deferred = new DeferredFuture();

        /** @var ?Delivery $nsqDelivery */
        $nsqDelivery = null;
        $consumer->consume('test', 'channel0', static function (Delivery $delivery) use (&$nsqDelivery, $deferred): void {
            $nsqDelivery = $delivery;
            $delivery->fin();
            $deferred->complete(true);
        });
        $consumer->run();
        $deferred->getFuture()->await();

        self::assertNotNull($nsqDelivery);
        self::assertSame('aaa', $nsqDelivery->body);
    }
}
