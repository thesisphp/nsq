<?php

declare(strict_types=1);

namespace Thesis\Nsq;

use Thesis\Nsq\Exception\InvalidName;
use Thesis\Nsq\Internal\Protocol;

/**
 * @api
 */
final class Topic implements \Stringable
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
     * @param non-empty-string|self $topic
     * @throws InvalidName
     */
    public static function create(string|self $topic): self
    {
        if (\is_string($topic)) {
            $topic = new self($topic);
        }

        return $topic;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
