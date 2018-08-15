<?php
/**
 * Sync
 * User: moyo
 * Date: 03/11/2017
 * Time: 11:55 AM
 */

namespace Carno\Promise\Features;

use Carno\Promise\Promised;
use Throwable;

trait Sync
{
    /**
     * @param Promised $next
     * @return Promised
     * @throws Throwable
     */
    public function sync(Promised $next) : Promised
    {
        $this->then(static function (...$args) use ($next) {
            $next->resolve(...$args);
        }, static function (...$args) use ($next) {
            $next->reject(...$args);
        });

        /**
         * @var Promised $this
         */

        return $this;
    }
}
