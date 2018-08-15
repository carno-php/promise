<?php
/**
 * GC test
 * User: moyo
 * Date: 15/03/2018
 * Time: 12:05 PM
 */

namespace Carno\Promise\Tests;

use Carno\Promise\Promise;
use Carno\Promise\Stats;
use PHPUnit\Framework\TestCase;

class GCTest extends TestCase
{
    public function testRacePending1()
    {
        $this->assertEquals(0, Stats::pending());

        $p1 = Promise::deferred();
        $p2 = Promise::deferred();
        $p3 = Promise::deferred();

        $pr = Promise::race($p1, $p2, $p3);

        $this->assertEquals(8, Stats::pending());

        $p2->resolve();

        if (!$p1->pended()) {
            $p1 = null;
            $this->assertEquals(3, Stats::pending());
        }

        if (!$p3->pended()) {
            $p3 = null;
            $this->assertEquals(2, Stats::pending());
        }

        $p2 = null;
        $this->assertEquals(1, Stats::pending());

        $pr = null;
        $this->assertEquals(0, Stats::pending());
    }

    public function testRacePending2()
    {
        $this->assertEquals(0, Stats::pending());

        $p2 = Promise::deferred();

        Promise::race(Promise::deferred(), $p2, Promise::deferred());

        $p2->reject();

        $this->assertEquals(1, Stats::pending());

        $p2 = null;
        $this->assertEquals(0, Stats::pending());
    }

    public function testAllPending1()
    {
        $this->assertEquals(0, Stats::pending());

        $p1 = Promise::deferred();
        $p2 = Promise::deferred();
        $p3 = Promise::deferred();

        $pa = Promise::all($p1, $p2, $p3);

        $this->assertEquals(8, Stats::pending());

        $p1->resolve();
        $p1 = null;

        $p3->resolve();
        $p3 = null;

        $this->assertEquals(6, Stats::pending());

        $p2->reject();
        $this->assertEquals(2, Stats::pending());

        $p2 = null;
        $this->assertEquals(1, Stats::pending());

        $pa = null;
        $this->assertEquals(0, Stats::pending());
    }

    public function testAllPending2()
    {
        $this->assertEquals(0, Stats::pending());

        $p1 = Promise::deferred();
        $p2 = Promise::deferred();
        $p3 = Promise::deferred();

        Promise::all($p1, $p2, $p3);

        $this->assertEquals(8, Stats::pending());

        $p1->resolve();
        $p2->resolve();
        $p3->resolve();
        $this->assertEquals(3, Stats::pending());

        $p1 = $p2 = $p3 = null;
        $this->assertEquals(0, Stats::pending());
    }

    public function testExceptionRefs1()
    {
        $this->assertEquals(0, Stats::pending());

        $em = '';

        Promise::rejected(new \Exception('test333'))->catch(function (\Throwable $e) use (&$em) {
            $em = $e->getMessage();
        });

        $this->assertEquals('test333', $em);

        $this->assertEquals(0, Stats::pending());
    }

    public function testExceptionRefs2()
    {
        $this->assertEquals(0, Stats::pending());

        $em = '';

        $p = Promise::deferred();

        $l2 = $p->then(function () {
            throw new \Exception('test233');
        });

        $em2 = '';

        $l2->then(null, function (\Throwable $e) use (&$em2) {
            $em2 = $e->getMessage();
        });

        $l2->catch(function (\Throwable $e) use (&$em) {
            $em = $e->getMessage();
        });

        $em3 = '';

        $l2->then(null, function (\Throwable $e) use (&$em3) {
            $em3 = $e->getMessage();
        });

        $p->resolve();

        $this->assertEquals('test233', $em);
        $this->assertEquals('test233', $em2);
        $this->assertEquals('test233', $em3);

        $p = $l2 = null;

        $this->assertTrue(gc_collect_cycles() > 0);

        $this->assertEquals(0, Stats::pending());
    }
}
