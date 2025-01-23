<?php

declare(strict_types=1);

namespace Thesis\Nsq\Exception;

use Thesis\Nsq\NsqException;

/**
 * @api
 */
final class InvalidName extends \InvalidArgumentException implements NsqException
{
    public static function itIsEmpty(): self
    {
        return new self('Name must not be empty.');
    }

    /**
     * @param non-empty-string $name
     */
    public static function tooLong(string $name, int $maxLength): self
    {
        return new self(\sprintf('The name "%s" is too long (%d). Allowed length is %d bytes.', $name, \strlen($name), $maxLength));
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $pattern
     */
    public static function badPattern(string $name, string $pattern): self
    {
        return new self(\sprintf('The name "%s" does not match the pattern "%s".', $name, $pattern));
    }
}
