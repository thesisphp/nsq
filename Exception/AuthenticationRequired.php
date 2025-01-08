<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class AuthenticationRequired extends \LogicException implements NsqException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('Nsq server requires authentication, but no authentication secret was configured.', $code, $previous);
    }
}
