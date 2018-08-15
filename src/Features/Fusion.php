<?php
/**
 * Fusion
 * User: moyo
 * Date: 2018/4/3
 * Time: 10:32 PM
 */

namespace Carno\Promise\Features;

use Carno\Promise\Promised;
use Throwable;

trait Fusion
{
    /**
     * @var bool
     */
    private $fusion = false;

    /**
     * @return Promised
     */
    public function fusion() : Promised
    {
        $this->fusion = true;

        $this->catch(static function (Throwable $e = null) {
            if ($e) {
                throw $e;
            }
        });

        /**
         * @var Promised $this
         */

        return $this;
    }
}
