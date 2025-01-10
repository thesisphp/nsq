<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class ImpossibleToDecompress extends \RuntimeException implements NsqException {}
