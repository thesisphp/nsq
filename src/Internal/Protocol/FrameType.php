<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 */
enum FrameType: int
{
    case Response = 0;
    case Error = 1;
    case Message = 2;
}
