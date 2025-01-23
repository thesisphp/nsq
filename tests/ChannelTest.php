<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Nsq\Exception\InvalidName;
use Thesis\Nsq\Internal\Protocol\Name;

#[CoversClass(Channel::class)]
#[CoversClass(Name::class)]
final class ChannelTest extends TestCase
{
    public function testChannelNameIsEmpty(): void
    {
        self::expectException(InvalidName::class);
        self::expectExceptionMessage('Name must not be empty.');
        new Channel('');
    }

    public function testChannelNameIsTooLong(): void
    {
        $name = str_repeat('x', 65);

        self::expectException(InvalidName::class);
        self::expectExceptionMessage(\sprintf('The name "%s" is too long (65). Allowed length is 64 bytes.', $name));
        new Channel($name);
    }

    public function testChannelNameDoesNotMatchPattern(): void
    {
        self::expectException(InvalidName::class);
        self::expectExceptionMessage('The name "канал" does not match the pattern "/^[.a-zA-Z0-9_-]+(#ephemeral)?$/".');
        new Channel('канал');
    }

    public function testChannelNameIsValid(): void
    {
        $channel = new Channel('events.listener');
        self::assertSame('events.listener', (string) $channel);
    }
}
