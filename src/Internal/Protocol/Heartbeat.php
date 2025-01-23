<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 */
enum Heartbeat implements Frame
{
    case frame;
}
