# Memcached

## Installation

```shell
composer require thesis/memcached
```

## Supported protocols
- [x] `text`
- [x] `binary`

## Connection

```php
<?php

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211?proto=text'); // connect via `text` protocol
$client = Memcached\connect('127.0.0.1:11211?proto=binary'); // connect via `binary` protocol
```

Without specifying a `proto`, `text` protocol will be used.

## Commands
- [Set](#set)
- [Add](#add)
- [Append](#append)
- [Prepend](#prepend)
- [Replace](#replace)
- [Get](#get)
- [Gets](#gets)
- [Delete](#delete)
- [Touch](#touch)
- [Incr](#incr)
- [Decr](#decr)
- [Cas](#cas)
- [Flush](#flush)
- [Verbosity](#verbosity)
- [Stats](#stats)

### Set

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->set(new Memcached\Key('x'), new Memcached\Item('y', expiration: Memcached\Expiration::fromSeconds(10)));
```

### Add

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->add(new Memcached\Key('x'), new Memcached\Item('y'));
```

### Append

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->append(new Memcached\Key('x'), new Memcached\Item('y'));
```

### Prepend

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->prepend(new Memcached\Key('x'), new Memcached\Item('y'));
```

### Replace

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->replace(new Memcached\Key('x'), new Memcached\Item('y'));
```

### Get

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$item = $client->get(new Memcached\Key('x'));

// Null means key doesn't exist.
if (null !== $item) {
    //
}
```

### Gets

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');

$client->set(new Memcached\Key('x'), new Memcached\Item('y'));
$client->set(new Memcached\Key('a'), new Memcached\Item('b'));

foreach ($client->gets([new Memcached\Key('x'), new Memcached\Key('a')]) as $key => $item) {
    //
}
```

### Delete

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->set(new Memcached\Key('x'), new Memcached\Item('y'));
$client->delete(new Memcached\Key('x'));
```

### Touch

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->set(new Memcached\Key('x'), new Memcached\Item('y', expiration: Memcached\Expiration::fromSeconds(10)));
$client->touch(new Memcached\Key('x'), Memcached\Expiration::fromSeconds(20));
```

### Incr

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->set(new Memcached\Key('x'), new Memcached\Item('1'));
$client->incr(new Memcached\Key('x'), 1);
```

### Decr

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->set(new Memcached\Key('x'), new Memcached\Item('1'));
$client->decr(new Memcached\Key('x'), 1);
```

### Cas

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');

$client->set(new Memcached\Key('x'), new Memcached\Item('y'));
$client->set(new Memcached\Key('a'), new Memcached\Item('b'));

foreach ($client->gets([new Memcached\Key('x'), new Memcached\Key('a')]) as $key => $item) {
    if ($key === 'x') {
        $client->cas(new Memcached\Key('x'), new Memcached\Item('z'));
    }
}
```

### Flush

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->set(new Memcached\Key('x'), new Memcached\Item('y'));
$client->set(new Memcached\Key('a'), new Memcached\Item('b'));
$client->flush();
```

### Verbosity

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
$client->verbosity(10);
$client->set(new Memcached\Key('x'), new Memcached\Item('y'));
```

### Stats

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Memcached;

$client = Memcached\connect('127.0.0.1:11211');
foreach ($client->stats() as $name => $stat) {
    echo $name . \PHP_EOL;
    echo $stat->value . \PHP_EOL;
}
```
