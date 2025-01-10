<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\Internal\Protocol\Frame;
use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class UnexpectedFrame extends \UnexpectedValueException implements NsqException
{
    public static function forType(int $type): self
    {
        return new self(\sprintf('Unexpected frame type "%d".', $type));
    }

    public static function forFrame(Frame $frame): self
    {
        return new self(\sprintf('Unexpected frame: "%s".', $frame::class));
    }
}
