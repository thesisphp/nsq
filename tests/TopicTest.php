<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Nsq\Exception\InvalidName;
use Thesis\Nsq\Internal\Protocol\Name;

#[CoversClass(Topic::class)]
#[CoversClass(Name::class)]
final class TopicTest extends TestCase
{
    public function testTopicNameIsEmpty(): void
    {
        self::expectException(InvalidName::class);
        self::expectExceptionMessage('Name must not be empty.');
        new Topic('');
    }

    public function testTopicNameIsTooLong(): void
    {
        $name = str_repeat('x', 65);

        self::expectException(InvalidName::class);
        self::expectExceptionMessage(\sprintf('The name "%s" is too long (65). Allowed length is 64 bytes.', $name));
        new Topic($name);
    }

    public function testTopicNameDoesNotMatchPattern(): void
    {
        self::expectException(InvalidName::class);
        self::expectExceptionMessage('The name "топик" does not match the pattern "/^[.a-zA-Z0-9_-]+(#ephemeral)?$/".');
        new Topic('топик');
    }

    public function testTopicNameIsValid(): void
    {
        $topic = new Topic('events');
        self::assertSame('events', (string) $topic);
    }
}
