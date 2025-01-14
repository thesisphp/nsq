<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
enum DeliveryState
{
    case Received;
    case Requeued;
    case Finished;
    case Touched;

    public function completed(): bool
    {
        return \in_array($this, [self::Finished, self::Requeued], true);
    }
}
