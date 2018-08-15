<?php
/**
 * Promise stats
 * User: moyo
 * Date: 13/03/2018
 * Time: 3:50 PM
 */

namespace Carno\Promise;

use Closure;

final class Stats
{
    /**
     * actions
     */
    public const PROPOSED = 0x1;
    public const CONFIRMED = 0x2;

    /**
     * @var int
     */
    private static $pending = 0;

    /**
     * @var Closure
     */
    private static $observer = null;

    /**
     * @return int
     */
    public static function pending() : int
    {
        return self::$pending;
    }

    /**
     * @param Closure $set
     */
    public static function hooking(?Closure $set) : void
    {
        self::$observer = $set;
    }

    /**
     * @param Promised $ins
     */
    public static function proposed(Promised $ins) : void
    {
        self::$pending ++;
        self::$observer && (self::$observer)(self::PROPOSED, $ins);
    }

    /**
     * @param Promised $ins
     */
    public static function confirmed(Promised $ins) : void
    {
        self::$pending --;
        self::$observer && (self::$observer)(self::CONFIRMED, $ins);
    }
}
