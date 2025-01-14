<?php

declare(strict_types=1);

namespace Typhoon\Nsq\Exception;

use Typhoon\Nsq\Internal\DeliveryState;
use Typhoon\Nsq\NsqException;

/**
 * @api
 */
final class MessageWasProcessed extends \LogicException implements NsqException
{
    public static function fromState(DeliveryState $state): self
    {
        return new self(\sprintf('Message is already %s.', match ($state) {
            DeliveryState::Requeued => 'requeued',
            DeliveryState::Finished => 'finished',
            default => 'unreachable',
        }));
    }
}
