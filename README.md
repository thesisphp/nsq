# Nsq

## Installation

```shell
composer require thesis/nsq
```

## Usage

### Producer

Since `nsq` does not have a cluster in the classical sense, you must specify the address of a particular `nsqd` host when publishing a message.
Typically, each `nsqd` instance is running on the same host as your application instance, so the request is actually made to a `localhost`.

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Thesis\Nsq;

$producer = new Nsq\Producer('tcp://127.0.0.1:4150');
```

To publish a message, you need to call one of the methods:

#### **`pub`**

To publish a single message.

```php
<?php

$producer->pub('test', 'a message');
```

#### **`dpub`**

To publish a single delayed message.

```php
<?php

$producer->dpub('test', 'a message', 3000);
```

The delay is specified in **milliseconds**.

#### **`mpub`**

To publish multiple messages.

```php
<?php

$producer->mpub('test', ['first message', 'second message']);
```

#### **`publish`**

All of this can also be done using the `publish` method. The main difference is that instead of a string you pass a `Thesis\Nsq\Message` object or a list of such objects, depending on which the necessary methods will be called:
- `pub` - if a `Thesis\Nsq\Message` object is passed;
- `dpub` - if the `Thesis\Nsq\Message` object has a `delay`;
- `mpub` - if a list of `Thesis\Nsq\Message` is passed.

```php
<?php

use Thesis\Nsq;

$producer->publish('test', new Nsq\Message(
    body: 'a message',
));
```

### Consumer

To let the consumer know which `nsqd` hosts has the desired topics, you need to pass the hosts to the `nsqlookupd` daemons.

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Thesis\Nsq;

$supervisor = new Nsq\ConsumerSupervisor(
    new Nsq\LookupConfig(hosts: [
        'http://127.0.0.1:4161',
        'http://127.0.0.1:4162',
        'http://127.0.0.1:4163',
    ]),
);
```

#### lookup `interval`

The `ConsumerSupervisor` will periodically query `nsqdlookupd` for new `nsqd` hosts. By default, it requests new hosts every **15 seconds**.
You can customize this by specifying your own interval in **seconds**.

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Thesis\Nsq;

$supervisor = new Nsq\ConsumerSupervisor(
    new Nsq\LookupConfig(hosts: ['http://127.0.0.1:4161'], interval: 10),
);
```

#### lookup `attempts`

By default, the supervisor will attempt to get the host up to **5 times**. You can configure this as well.

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Thesis\Nsq;

$supervisor = new Nsq\ConsumerSupervisor(
    new Nsq\LookupConfig(hosts: ['http://127.0.0.1:4161'], attempts: 2),
);
```

You can also configure `sleep`, `maxSleep` and `jitter` parameters via `Thesis\Nsq\LookupConfig`. Customize these parameters as you see fit, but now let's consume.

#### consume via `callable`

You can handle messages through a regular `callable` by passing it to the consumer.

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Thesis\Nsq;

$supervisor = new Nsq\ConsumerSupervisor(
    new Nsq\LookupConfig(hosts: ['http://127.0.0.1:4161']),
);

$supervisor->consume(topic: 'test', channel: 'logs', consumer: static function (Nsq\Delivery $delivery): void {
    var_dump($delivery);
});

$supervisor->run();
```

*Don't forget to call `ConsumerSupervisor::run` after the consumers have been configured.*

Your handler will receive a `Thesis\Nsq\Delivery` for which you must call one of the following methods:
- `fin` - if you have processed the message successfully;
- `touch` - if you are processing messages for a long time, you should call this method to avoid a timeout by `nsq`;
- `requeue` - if you want to process the message again after some time. The time, as in the case of `dpub`, is specified in **milliseconds**.

In addition to the message body (`Thesis\Nsq\Delivery::$body`), you have access to the number of attempts (`Thesis\Nsq\Delivery::$attempts`), the timestamp (`Thesis\Nsq\Delivery::$timestamp`) the message was published, and the message id (`Thesis\Nsq\Delivery::$id`).

You can use the number of attempts to organize processing limits and gradually increase the time between attempts (also called `jitter`):

```php
<?php

declare(strict_types=1);

use Thesis\Nsq;

const maxRetryAttempt = 10;
const delayInterval = 1000;

$supervisor->consume(topic: 'test', channel: 'logs', consumer: static function (Nsq\Delivery $delivery): void {
    try {
        // handle
    } catch (\Throwable $e) {
        if ($delivery->attempts < maxRetryAttempt) {
            $delivery->requeue(delayInterval * $delivery->attempts);
        } else {
            $delivery->fin();
        }
    }
});
```

Or you can set a time limit before which the message must be processed.

```php
<?php

declare(strict_types=1);

use Thesis\Nsq;

const timeThreshold = 10; // in seconds

$supervisor->consume(topic: 'test', channel: 'logs', consumer: static function (Nsq\Delivery $delivery): void {
    if ($delivery->dateTime()->modify(\sprintf('+%d seconds', timeThreshold)) > new \DateTimeImmutable('now')) {
       // handle
    }
});
```

#### consume via `Thesis\Nsq\Consumer`

To specify `rdy` count you should `Consumer` object:

```php
<?php

declare(strict_types=1);

$consumer->consume('test', 'channel0', new Nsq\Consumer(
    callback: static function (Nsq\Delivery $delivery): void {
        // handle
    },
    rdy: 5,
));
```

Now 5 messages will be pushed to the client as they are available (not in batch).

The `rdy` works as a `prefetch count` from the `amqp` protocol, but **you can't `fin` the whole batch in nsq**.

### Authentication

If an authentication server is configured, you must specify the secret that the client will send to `nsqd`.
You can do this via `Thesis\Nsq\Config` when creating a producer or a consumer, because they use the same config.

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use Thesis\Nsq;

$producer = new Nsq\Producer('tcp://127.0.0.1:4150', new Nsq\Config(
    authenticationSecret: 'secret',
));
```

### Examples

More examples can be found [here](examples).
