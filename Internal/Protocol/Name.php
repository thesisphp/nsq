<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

use Typhoon\Nsq\Exception\InvalidName;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Name
{
    /** @var int in ASCII */
    private const NAME_MAX_LENGTH = 64;
    private const NAME_REGEX = '/^[.a-zA-Z0-9_-]+(#ephemeral)?$/';

    /**
     * @return non-empty-string
     * @throws InvalidName
     */
    public static function validate(string $name): string
    {
        if ($name === '') {
            throw InvalidName::itIsEmpty();
        }

        if (\strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidName::tooLong($name, self::NAME_MAX_LENGTH);
        }

        if ((bool) preg_match(self::NAME_REGEX, $name) === false) {
            throw InvalidName::badPattern($name, self::NAME_REGEX);
        }

        return $name;
    }
}
