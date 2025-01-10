<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class ImpossibleToCompress extends \RuntimeException implements NsqException {}
