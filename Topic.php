<?php

declare(strict_types=1);

namespace Typhoon\Nsq;

use Typhoon\Nsq\Exception\InvalidName;
use Typhoon\Nsq\Internal\Protocol;

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
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
