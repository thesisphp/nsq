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
