<?php

declare(strict_types=1);

namespace Thesis\Nsq\Exception;

use Thesis\Nsq\Internal\DeliveryState;
use Thesis\Nsq\NsqException;

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
