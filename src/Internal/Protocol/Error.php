<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 */
final class Error implements Frame, \Stringable
{
    public function __construct(
        public readonly ErrorType $type,
        public readonly string $explanation,
    ) {}

    public static function parse(string $error): self
    {
        $chunks = explode(' ', $error);

        return new self(
            ErrorType::tryFrom($chunks[0]) ?: ErrorType::E_INVALID,
            implode(' ', \array_slice($chunks, 1)),
        );
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return "{$this->type->value}({$this->explanation})";
    }
}
