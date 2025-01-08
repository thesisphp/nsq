<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 */
final class Frame
{
    public function __construct(
        public readonly Response|Error|Message $value,
    ) {}
}
