<?php

declare(strict_types=1);

namespace Thesis\Nsq\Exception;

use Thesis\Nsq\Internal\Protocol\Frame;
use Thesis\Nsq\NsqException;

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
