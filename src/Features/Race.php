<?php
/**
 * Race
 * User: moyo
 * Date: 04/08/2017
 * Time: 12:26 AM
 */

namespace Carno\Promise\Features;

use Carno\Promise\Exception\RacingLoser;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Promise\Stacked;
use Throwable;

trait Race
{
    /**
     * @param Promised ...$promises
     * @return Promised
     */
    public static function race(Promised ...$promises) : Promised
    {
        /**
         * @var Promise $race
         */

        $race = Promise::deferred();

        $sid = Stacked::in(...$promises);

        foreach ($promises as $promise) {
            $promise->then(static function (...$args) use ($race, $sid) {
                if ($race->pended()) {
                    $race->resolve(...$args);
                    $race->rpInterrupt($sid);
                }
            }, static function (...$args) use ($race) {
                if ($race->pended()) {
                    $race->reject(...$args);
                }
            });
        }

        $race->then(null, static function (...$args) use ($race, $sid) {
            $race->rpInterrupt($sid, $race->rpException(...$args));
        });

        return $race;
    }

    /**
     * @param int $sid
     * @param Throwable $e
     */
    private function rpInterrupt(int $sid, Throwable $e = null) : void
    {
        foreach (Stacked::out($sid) as $promised) {
            if ($promised->pended()) {
                $e ? $promised->throw($e) : $promised->reject();
            }
        }
    }

    /**
     * @param mixed $test
     * @return Throwable
     */
    private function rpException($test = null) : Throwable
    {
        return $test instanceof Throwable ? $test : new RacingLoser;
    }
}
