<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Nsq;

$producer = new Nsq\Producer('tcp://nsqd0:4150', new Nsq\Config(
    authenticationSecret: 'jV22WdmaXxHWAiAh',
));

$producer->pub('test', 'aaa');
$producer->close();
