<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Typhoon\Nsq\Exception\InvalidName;
use Typhoon\Nsq\Internal\Protocol;

/**
 * @api
 */
final class Channel implements \Stringable
{
    /** @var non-empty-string */
    public readonly string $name;

    /**
     * @throws InvalidName
     */
    public function __construct(string $name)
    {
        $this->name = Protocol\Name::validate($name);
    }

    /**
     * @param non-empty-string|self $channel
     * @throws InvalidName
     */
    public static function create(string|self $channel): self
    {
        if (\is_string($channel)) {
            $channel = new self($channel);
        }

        return $channel;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
