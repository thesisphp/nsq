<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class ExtensionNotAvailable extends \RuntimeException implements NsqException
{
    public static function forSnappy(): self
    {
        return new self('Snappy extension is not available. You should turn off snappy compression.');
    }

    public static function forZlib(): self
    {
        return new self('Zlib extension is not available. You should turn off zlib compression.');
    }
}
