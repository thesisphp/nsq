<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Nsq;
use function Amp\trapSignal;

$supervisor = new Nsq\ConsumerSupervisor(
    new Nsq\LookupConfig(hosts: [
        'http://nsqlookupd0:4161',
    ]),
    new Nsq\Config(authenticationSecret: 'jV22WdmaXxHWAiAh'),
);

$supervisor->consume('test', 'logs', static function (Nsq\Delivery $delivery): void {
    var_dump(\sprintf('Message from "logs" channel: %s', $delivery->body));
    $delivery->fin();
});

$supervisor->consume('test', 'handle', static function (Nsq\Delivery $delivery): void {
    var_dump(\sprintf('Message from "handle" channel: %s', $delivery->body));
    $delivery->fin();
});

$supervisor->run();

trapSignal([\SIGINT, \SIGTERM]);

$supervisor->stop();
