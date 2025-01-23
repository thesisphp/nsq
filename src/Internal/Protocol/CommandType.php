<?php

declare(strict_types=1);

namespace Thesis\Nsq\Internal\Protocol;

/**
 * @internal
 */
enum CommandType: string
{
    case Magic = '  V2';
    case Identify = 'IDENTIFY';
    case Auth = 'AUTH';
    case Rdy = 'RDY';
    case Nop = 'NOP';
    case Cls = 'CLS';
    case Fin = 'FIN';
    case Req = 'REQ';
    case Touch = 'TOUCH';
    case Pub = 'PUB';
    case Mpub = 'MPUB';
    case DPub = 'DPUB';
    case Sub = 'SUB';
}
