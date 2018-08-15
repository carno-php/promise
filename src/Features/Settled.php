<?php
/**
 * Settled
 * User: moyo
 * Date: 15/09/2017
 * Time: 10:11 PM
 */

namespace Carno\Promise\Features;

use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Throwable;

trait Settled
{
    /**
     * @param mixed ...$data
     * @return Promised
     */
    public static function resolved(...$data)
    {
        return new Promise(static function (Promised $p) use ($data) {
            $p->resolve(...$data);
        });
    }

    /**
     * @param mixed ...$data
     * @return Promised
     * @throws Throwable
     */
    public static function rejected(...$data)
    {
        return new Promise(static function (Promised $p) use ($data) {
            $p->reject(...$data);
        });
    }
}
