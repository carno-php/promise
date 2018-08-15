<?php
/**
 * All
 * User: moyo
 * Date: 04/08/2017
 * Time: 11:04 AM
 */

namespace Carno\Promise\Features;

use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Promise\Stacked;

trait All
{
    /**
     * @var array
     */
    private $resolved = [];

    /**
     * @param Promised ...$promises
     * @return Promised
     */
    public static function all(Promised ...$promises) : Promised
    {
        if (empty($promises)) {
            return Promise::resolved();
        }

        /**
         * @var Promise $all
         */

        $all = Promise::deferred();

        $sid = Stacked::in(...$promises);

        foreach ($promises as $pid => $promise) {
            $promise->then(static function (...$args) use ($all, $sid, $pid) {
                $all->pended() && $all->apResolving($sid, $pid, ...$args);
            }, static function (...$args) use ($all) {
                $all->pended() && $all->reject(...$args);
            });
        }

        $all->then(null, static function (...$args) use ($sid) {
            foreach (Stacked::out($sid) as $promise) {
                $promise->pended() && $promise->reject(...$args);
            }
        });

        return $all;
    }

    /**
     * @param int $sid
     * @param int $pid
     * @param mixed ...$args
     */
    private function apResolving(int $sid, int $pid, ...$args)
    {
        $this->resolved[$pid] = count($args) > 1 ? $args : current($args);

        if (count($this->resolved) === Stacked::num($sid)) {
            Stacked::out($sid) && $this->resolve($this->resolved);
        }
    }
}
