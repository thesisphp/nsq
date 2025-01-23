<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Nsq\Exception\MessageWasProcessed;

#[CoversClass(Delivery::class)]
final class DeliveryTest extends TestCase
{
    public function testDeliveryAlreadyFinished(): void
    {
        $client = $this->createMock(Stub\Client::class);
        $client
            ->expects(self::once())
            ->method('fin')
            ->with('x');

        /** @var Stub\Client $nsq */
        $nsq = $client;

        $delivery = new Delivery(
            $nsq->fin(...),
            $nsq->touch(...),
            $nsq->requeue(...),
            'x',
            '{}',
            0,
            0,
        );

        $delivery->fin();

        self::expectException(MessageWasProcessed::class);
        self::expectExceptionMessage('Message is already finished.');
        $delivery->fin();
    }

    public function testDeliveryAlreadyRequeued(): void
    {
        $client = $this->createMock(Stub\Client::class);
        $client
            ->expects(self::once())
            ->method('requeue')
            ->with('x', 2000);

        /** @var Stub\Client $nsq */
        $nsq = $client;

        $delivery = new Delivery(
            $nsq->fin(...),
            $nsq->touch(...),
            $nsq->requeue(...),
            'x',
            '{}',
            0,
            0,
        );

        $delivery->requeue(2000);

        self::expectException(MessageWasProcessed::class);
        self::expectExceptionMessage('Message is already requeued.');
        $delivery->requeue(2000);
    }

    public function testDeliveryTouchedMultiple(): void
    {
        $client = $this->createMock(Stub\Client::class);
        $client
            ->expects(self::exactly(2))
            ->method('touch')
            ->with('x');

        $client
            ->expects(self::once())
            ->method('fin')
            ->with('x');

        /** @var Stub\Client $nsq */
        $nsq = $client;

        $delivery = new Delivery(
            $nsq->fin(...),
            $nsq->touch(...),
            $nsq->requeue(...),
            'x',
            '{}',
            0,
            0,
        );

        $delivery->touch();
        $delivery->touch();
        $delivery->fin();
    }

    public function testDeliveryTouchedAfterFinished(): void
    {
        $client = $this->createMock(Stub\Client::class);
        $client
            ->expects(self::once())
            ->method('fin')
            ->with('x');

        /** @var Stub\Client $nsq */
        $nsq = $client;

        $delivery = new Delivery(
            $nsq->fin(...),
            $nsq->touch(...),
            $nsq->requeue(...),
            'x',
            '{}',
            0,
            0,
        );

        $delivery->fin();

        self::expectException(MessageWasProcessed::class);
        self::expectExceptionMessage('Message is already finished.');
        $delivery->touch();
    }

    public function testDeliveryTouchedAfterRequeued(): void
    {
        $client = $this->createMock(Stub\Client::class);
        $client
            ->expects(self::once())
            ->method('requeue')
            ->with('x', 2000);

        /** @var Stub\Client $nsq */
        $nsq = $client;

        $delivery = new Delivery(
            $nsq->fin(...),
            $nsq->touch(...),
            $nsq->requeue(...),
            'x',
            '{}',
            0,
            0,
        );

        $delivery->requeue(2000);

        self::expectException(MessageWasProcessed::class);
        self::expectExceptionMessage('Message is already requeued.');
        $delivery->touch();
    }
}
