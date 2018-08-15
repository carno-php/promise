# Promise - component of carno-php

# Features

- [Promises/A+](https://promisesaplus.com/) standards
- Addition commands e.g. race, all
- Simple and fast

# Installation

```bash
composer require carno-php/promise
```

# API & Usages

#### new Promise

Creates a promise with initialize executor

```php
$promise = new Promise(static function (Promised $promise) {
    if (1) {
        $promise->resolve('success');
    } else {
        $promise->reject('error');
    }
});
$promise->then(function () {
    echo 'promise has been resolved', PHP_EOL;
}, function () {
    echo 'promise has been rejected', PHP_EOL;
});
```

#### Promise::deferred

Creates a defer resolve promise

```php
$promise = Promise::deferred();
$promise->then(function (string $var) {
    echo $var, PHP_EOL;
});
$promise->resolve('var');
```

#### Promise::resolved

Creates a resolved promise

```php
$promise = Promise::resolved('var');
$promise->then(function (string $var) {
    echo $var, PHP_EOL;
});
```

#### Promise::rejected

Creates a rejected promise

```php
$promise = Promise::rejected(new Exception('Test'));
$promise->catch(function (Throwable $e) {
    echo 'failure with ', $e->getMessage(), PHP_EOL;
});
```

#### Promise::all

> Returns a single promise that resolves when all of the promises in the argument have resolved or when the argument contains no promises. It rejects with the reason of the first promise that rejects.

```php
$p1 = Promise::deferred();
$p2 = Promise::deferred();
$pa = Promise::all($p1, $p2);
$pa->then(function (array $results) {
    echo 'all resovled with results ', var_export($results, true), PHP_EOL;
});
$p1->resolve('var1');
$p2->resolve('var2');
```

#### Promise::race

> Returns a promise that resolves or rejects as soon as one of the promises in the argument resolves or rejects, with the value or reason from that promise.

```php
$p1 = Promise::deferred();
$p1->then(function () {
    echo 'p1 has been resolved', PHP_EOL;
}, function () {
    echo 'p1 has been rejected', PHP_EOL;
});
$p2 = Promise::deferred();
$p2->then(function () {
    echo 'p2 has been resolved', PHP_EOL;
}, function () {
    echo 'p2 has been rejected', PHP_EOL;
});
$pr = Promise::race($p1, $p2);
$pr->then(function (string $var) {
    echo 'race result is ', $var, PHP_EOL;
});
$p1->resolve('test');
// or $p2->reject();
```

#### Promise->pended

Check that promise is neither fulfilled and rejected

```php
$promise = Promise::deferred();
echo '#1 promise is pended ? ', $promise->pended() ? 'yes' : 'no', PHP_EOL;
$promise->resolve();
echo '#2 promise is pended ? ', $promise->pended() ? 'yes' : 'no', PHP_EOL;
```

#### Promise->chained

Check that promise has more chained promises (connected with ```then```)

```php
$promise = Promise::deferred();
echo '#1 promise is chained ? ', $promise->chained() ? 'yes' : 'no', PHP_EOL;
$promise->then(function () {
});
echo '#2 promise is chained ? ', $promise->chained() ? 'yes' : 'no', PHP_EOL;
```

#### Promise->sync

Make promise synced with other promise (resolves and rejects)

```php
$next = Promise::deferred();
$next->then(function (string $var) {
    echo 'NEXT promise been resolved with ', $var, PHP_EOL;
}, function (string $var) {
    echo 'NEXT promise been rejected with ', $var, PHP_EOL;
});
$promise = Promise::deferred()->sync($next);
$promise->then(function (string $var) {
    echo 'CURRENT promise been resolved with ', $var, PHP_EOL;
}, function (string $var) {
    echo 'CURRENT promise been rejected with ', $var, PHP_EOL;
});
$promise->resolve('hello');
// or $promise->reject('world');
```

#### Promise->fusion

Set promise to throws exception if rejected with an error, otherwise exception will only as promise's result

```php
Promise::deferred()->fusion()->throw(new Exception('test'));
```

#### Promise->then

```php
$promise = Promise::deferred();
$promise->then(function (...$args) {
    echo 'promise resolved with args ', var_export($args, true), PHP_EOL;
}, function (...$args) {
    echo 'promise rejected with args ', var_export($args, true), PHP_EOL;
});
$promise->resolve('hello', 'world');
// or $promise->reject('hello', 'world');
```

#### Promise->catch

Alias of Promise->then(null, onRejects)

#### Promise->resolve

Resolves a promise

```php
Promise::deferred()->resolve('var1', 'var2');
```

#### Promise->reject

Rejects a promise

```php
Promise::deferred()->reject('var1', 'var2');
```

#### Promise->throw

Alias of Promise->reject(exception)

# Benchmark

```bash
php benchmark.php
```

output

```text
cost 362 ms | op 1.105 μs
```
