<?php

namespace BM_TEST;

require 'vendor/autoload.php';

use Carno\Promise\Promise;

$begin = microtime(true);

for ($r = 0; $r < 10240; $r ++) {
    $promise = $chain = Promise::deferred();

    for ($c = 0; $c < 32; $c ++) {
        $chain = $chain->then(static function () {
            // do nothing
        });
    }

    $promise->resolve();
}

$cost = round((microtime(true) - $begin) * 1000);

echo 'cost ', $cost, ' ms | op ', round($cost * 1000 / ($r * $c), 3), ' μs', PHP_EOL;
