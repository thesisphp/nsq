<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\Internal\Protocol;
use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class NsqError extends \RuntimeException implements NsqException
{
    public static function fromError(Protocol\Error $error): self
    {
        return new self(\sprintf('Unexpected NSQ error received: "%s" (%s).', $error->explanation, $error->type->value));
    }
}
