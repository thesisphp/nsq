<?php

declare(strict_types=1);

namespace Thesis\Nsq\Exception;

use Thesis\Nsq\Internal\Protocol;
use Thesis\Nsq\NsqException;

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
