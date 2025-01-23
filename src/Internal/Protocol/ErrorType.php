<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 */
enum ErrorType: string
{
    case E_INVALID = 'E_INVALID';
    case E_BAD_BODY = 'E_BAD_BODY';
    case E_BAD_TOPIC = 'E_BAD_TOPIC';
    case E_BAD_CHANNEL = 'E_BAD_CHANNEL';
    case E_BAD_MESSAGE = 'E_BAD_MESSAGE';
    case E_PUB_FAILED = 'E_PUB_FAILED';
    case E_MPUB_FAILED = 'E_MPUB_FAILED';
    case E_DPUB_FAILED = 'E_DPUB_FAILED';
    case E_FIN_FAILED = 'E_FIN_FAILED';
    case E_REQ_FAILED = 'E_REQ_FAILED';
    case E_TOUCH_FAILED = 'E_TOUCH_FAILED';
    case E_AUTH_FAILED = 'E_AUTH_FAILED';
    case E_UNAUTHORIZED = 'E_UNAUTHORIZED';
}
