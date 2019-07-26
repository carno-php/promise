<?php
/**
 * Promises stacked
 * User: moyo
 * Date: 15/03/2018
 * Time: 10:22 AM
 */

namespace Carno\Promise;

final class Stacked
{
    /**
     * @var int
     */
    private static $sid = 0;

    /**
     * @var array
     */
    private static $stacks = [];

    /**
     * @param Promised ...$promises
     * @return int
     */
    public static function in(Promised ...$promises) : int
    {
        self::$stacks[$sid = self::next()] = $promises;
        return $sid;
    }

    /**
     * @param int $sid
     * @return Promised[]
     */
    public static function out(int $sid) : array
    {
        $stack = self::$stacks[$sid] ?? [];
        unset(self::$stacks[$sid]);
        return $stack;
    }

    /**
     * @param int $sid
     * @return int
     */
    public static function num(int $sid) : int
    {
        return count(self::$stacks[$sid] ?? []);
    }

    /**
     * @return int
     */
    public static function size() : int
    {
        return count(self::$stacks);
    }

    /**
     * @return int
     */
    private static function next() : int
    {
        return self::$sid++ >= PHP_INT_MAX ? self::$sid = 1 : self::$sid;
    }
}
