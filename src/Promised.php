<?php
/**
 * Promise API
 * User: moyo
 * Date: 03/08/2017
 * Time: 11:11 AM
 */

namespace Carno\Promise;

use Throwable;

interface Promised
{
    /**
     * @return bool
     */
    public function pended() : bool;

    /**
     * @return bool
     */
    public function chained() : bool;

    /**
     * @param Promised $next
     * @return Promised
     */
    public function sync(Promised $next) : Promised;

    /**
     * @return Promised
     */
    public function fusion() : Promised;

    /**
     * @param callable $fulfilled
     * @param callable $rejected
     * @return Promised
     */
    public function then(callable $fulfilled = null, callable $rejected = null) : Promised;

    /**
     * @param callable $throwing
     * @return Promised
     */
    public function catch(callable $throwing) : Promised;

    /**
     * @param mixed ...$args
     */
    public function resolve(...$args) : void;

    /**
     * @param mixed ...$args
     */
    public function reject(...$args) : void;

    /**
     * @param Throwable $error
     */
    public function throw(Throwable $error) : void;
}
