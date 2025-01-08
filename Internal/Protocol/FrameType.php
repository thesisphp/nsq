<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
enum FrameType: int
{
    case Response = 0;
    case Error = 1;
    case Message = 2;
}
