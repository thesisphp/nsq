<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class ConnectionWasClosed extends \RuntimeException implements NsqException {}
