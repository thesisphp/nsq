<?php

declare(strict_types=1);

namespace Thesis\Nsq\Exception;

use Thesis\Nsq\NsqException;

/**
 * @api
 */
final class StreamIsNotReady extends \RuntimeException implements NsqException {}
