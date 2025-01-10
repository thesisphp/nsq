<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Internal\Protocol;

use Typhoon\Nsq\Channel;
use Typhoon\Nsq\Topic;

/**
 * @internal
 * @psalm-internal Typhoon\Nsq
 * @template-covariant T
 */
final class Command
{
    /**
     * @return self<void>
     */
    public static function magic(): self
    {
        /** @var self<void> */
        return new self(CommandType::Magic);
    }

    /**
     * @param non-empty-string $identify
     * @return self<ServerConfig>
     */
    public static function identify(string $identify): self
    {
        /** @var self<ServerConfig> */
        return new self(CommandType::Identify, body: $identify, parser: ServerConfig::fromJSON(...));
    }

    /**
     * @param non-empty-string $secret
     * @return self<AuthConfig>
     */
    public static function auth(string $secret): self
    {
        /** @var self<AuthConfig> */
        return new self(CommandType::Auth, body: $secret, parser: AuthConfig::fromJSON(...));
    }

    /**
     * @param non-negative-int $count
     * @return self<void>
     */
    public static function rdy(int $count): self
    {
        /** @var self<void> */
        return new self(CommandType::Rdy, [(string) $count]);
    }

    /**
     * @return self<void>
     */
    public static function nop(): self
    {
        /** @var self<void> */
        return new self(CommandType::Nop);
    }

    /**
     * @return self<void>
     */
    public static function cls(): self
    {
        /** @var self<void> */
        return new self(CommandType::Cls);
    }

    /**
     * @param non-empty-string $id
     * @return self<void>
     */
    public static function fin(string $id): self
    {
        /** @var self<void> */
        return new self(CommandType::Fin, [$id]);
    }

    /**
     * @param non-empty-string $id
     * @param non-negative-int $timeout
     * @return self<void>
     */
    public static function requeue(string $id, int $timeout): self
    {
        /** @var self<void> */
        return new self(CommandType::Req, [$id, (string) $timeout]);
    }

    /**
     * @param non-empty-string $id
     * @return self<void>
     */
    public static function touch(string $id): self
    {
        /** @var self<void> */
        return new self(CommandType::Touch, [$id]);
    }

    /**
     * @param non-empty-string $body
     * @return self<void>
     */
    public static function pub(Topic $topic, string $body): self
    {
        /** @var self<void> */
        return new self(CommandType::Pub, [(string) $topic], $body);
    }

    /**
     * @param non-empty-list<non-empty-string> $messages
     * @return self<void>
     */
    public static function mpub(Topic $topic, array $messages): self
    {
        /** @var self<void> */
        return new self(CommandType::Mpub, [(string) $topic], $messages);
    }

    /**
     * @param non-empty-string $body
     * @param non-negative-int $delay
     * @return self<void>
     */
    public static function dpub(Topic $topic, string $body, int $delay): self
    {
        /** @var self<void> */
        return new self(CommandType::DPub, [(string) $topic, (string) $delay], $body);
    }

    /**
     * @return self<void>
     */
    public static function sub(Topic $topic, Channel $channel): self
    {
        /** @var self<void> */
        return new self(CommandType::Sub, [(string) $topic, (string) $channel]);
    }

    public function writeTo(WriteBytes $writer): void
    {
        $writer->write(implode(' ', [$this->type->value, ...$this->args]));

        if ($this->type !== CommandType::Magic) {
            $writer->write(PHP_EOL);
        }

        if (\is_string($this->body)) {
            $writer
                ->writeUint32(\strlen($this->body))
                ->write($this->body);
        } elseif (\is_array($this->body)) {
            // Reserve 4 bytes (uint32) for message count.
            $bodySize = 4;

            foreach ($this->body as $body) {
                // Each message is a pair of message size (uint32) and message itself.
                $bodySize += \strlen($body) + 4;
            }

            $writer
                ->writeUint32($bodySize)
                ->writeUint32(\count($this->body))
                ->writeArray($this->body, $writer->writeUint32(...));
        }
    }

    /**
     * @return T
     * @throws \Throwable
     */
    public function parse(Response $response): mixed
    {
        if ($this->parser !== null) {
            return ($this->parser)($response->body);
        }

        return null;
    }

    /**
     * @param list<non-empty-string> $args
     * @param null|non-empty-string|non-empty-list<non-empty-string> $body
     * @param ?\Closure(non-empty-string): T $parser
     */
    private function __construct(
        private readonly CommandType $type,
        private readonly array $args = [],
        private readonly null|string|array $body = null,
        private readonly ?\Closure $parser = null,
    ) {}
}
