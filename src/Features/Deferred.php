<?php
/**
 * Deferred
 * User: moyo
 * Date: 04/08/2017
 * Time: 12:15 AM
 */

namespace Carno\Promise\Features;

use Carno\Promise\Promise;
use Carno\Promise\Promised;

trait Deferred
{
    /**
     * @return Promised
     */
    public static function deferred() : Promised
    {
        return new Promise(null);
    }
}
