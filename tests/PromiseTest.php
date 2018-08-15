<?php
/**
 * Promise test
 * User: moyo
 * Date: 03/08/2017
 * Time: 4:36 PM
 */

namespace Carno\Promise\Tests;

use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Promise\Stats;
use PHPUnit\Framework\TestCase;

class PromiseTest extends TestCase
{
    public function testComResolveReturn()
    {
        $r1 = $r2 = $f3 = null;

        $p = Promise::deferred();
        $p->then(function ($val) use (&$r1) {
            $r1 = $val;
        });
        $p->then(function ($val) use (&$r2) {
            $r2 = $val;
            return $val;
        })->then(function ($val) use (&$r3) {
            $r3 = $val;
        });

        $this->assertEquals(null, $r1);
        $this->assertEquals(null, $r2);
        $this->assertEquals(null, $r3);

        $p->resolve(2333);

        $this->assertEquals(2333, $r1);
        $this->assertEquals(2333, $r2);
        $this->assertEquals(2333, $r3);

        $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testComResolveChain()
    {
        $test1R = 0;

        $p = Promise::deferred();
        $p->then(function () use (&$test1R) {
            $test1R += 1;
        })->then(function () use (&$test1R) {
            $test1R += 2;
        })->then(function () use (&$test1R) {
            $test1R += 3;
        });

        $this->assertEquals(0, $test1R);

        $p->resolve();

        $this->assertEquals(6, $test1R);

        $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testYetResolveProc()
    {
        $r2 = null;

        $p = new Promise(function (Promised $p) {
            $p->resolve(123);
        });

        $p->then(function ($value) use (&$r2) {
            $r2 = $value;
        });

        $this->assertEquals(123, $r2);

        $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testPromiseOverPromise()
    {
        $pa = Promise::deferred();

        $r1 = $r2 = null;

        $p = new Promise(function (Promised $p) {
            $p->resolve();
        });
        $p->then(function () use (&$r1, &$pa) {
            $r1 = true;
            return $pa;
        })->then(function ($val) use (&$r2) {
            $r2 = $val;
        });

        $this->assertEquals(true, $r1);

        $this->assertEquals(true, $r1);
        $this->assertEquals(null, $r2);

        $pa->resolve(1234);

        $this->assertEquals(1234, $r2);

        $pa = $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testComRejectReturn()
    {
        $r1 = $r2 = $r3 = null;

        $p = Promise::deferred();
        $p->then(function () use (&$r1) {
            $r1 = true;
        }, function ($v) use (&$r2) {
            $r2 = true;
            return $v;
        })->then(null, function ($r) use (&$r3) {
            $r3 = $r;
        });

        $p->reject(2333);

        $this->assertEquals(null, $r1);
        $this->assertEquals(true, $r2);
        $this->assertEquals(2333, $r3);

        $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testNullsResolveChain()
    {
        $r1 = $r2 = null;

        $p = Promise::deferred();

        $p->then()->then()->then()->then(function ($v) use (&$r1) {
            $r1 = $v;
        }, function ($v) use (&$r2) {
            $r2 = $v;
        });

        $p->resolve(333);

        $this->assertEquals(333, $r1);

        $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testExceptionCatch()
    {
        $rs = $rf = $rf2 = null;

        $p = Promise::deferred();
        $p->then(function () use (&$rs) {
            $rs = true;
            throw new \Exception(1233);
        }, function () use (&$rf2) {
            $rf2 = true;
        })->catch(function (\Throwable $e) use (&$rf) {
            $rf = $e->getMessage();
        });

        $p->resolve();

        $this->assertEquals(true, $rs);
        $this->assertEquals(1233, $rf);
        $this->assertEquals(null, $rf2);

        $p = null;

        $this->assertTrue(gc_collect_cycles() > 0);

        $this->assertEquals(0, Stats::pending());
    }

    public function testExceptionThrow()
    {
        $last = new \Exception('non');

        try {
            $p = Promise::deferred();
            $p->then(function ($in) {
                return $in;
            })->then(function ($in) {
                if ($in === 'test-1') {
                    throw new \Exception('test-fail');
                }
            })->catch(function (\Exception $e) {
                if ($e->getMessage() === 'test-fail') {
                    throw new \Exception('test-fail2');
                }
            })->fusion();
            $p->resolve('test-1');
        } catch (\Exception $e) {
            $last = $e;
            $e = null;
        }

        $this->assertEquals('test-fail2', $last->getMessage());

        $p = null;

        $last = null;

        $this->assertTrue(gc_collect_cycles() > 0);

        $this->assertEquals(0, Stats::pending());
    }

    public function testCallableReturn()
    {
        $r1 = $r2 = null;

        $p = Promise::deferred();
        $p->then(function () {
            return function (Promised $promised) {
                $promised->resolve(222, 333);
            };
        })->then(function ($v1, $v2) use (&$r1, &$r2) {
            $r1 = $v1;
            $r2 = $v2;
        });

        $p->resolve();

        $this->assertEquals(222, $r1);
        $this->assertEquals(333, $r2);

        $p = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testFeatureRace()
    {
        $rs = $rf = null;

        $p1 = Promise::deferred();
        $p2 = Promise::deferred();

        // only winner can be resolved
        // all loser will be killed

        $race1 = Promise::race($p1, $p2);
        $race1->then(function ($v) use (&$rs) {
            $rs = $v;
        }, function ($v) use (&$rf) {
            $rf = $v;
        });

        $p1->resolve('vvv1');
        $this->assertEquals(false, $p2->pended());

        $this->assertEquals('vvv1', $rs);
        $this->assertEquals(null, $rf);

        $p3 = Promise::deferred();
        $p4 = Promise::deferred();

        $race2 = Promise::race($p3, $p4);
        $race2->then(function ($v) use (&$rs) {
            $rs = $v;
        }, function ($v) use (&$rf) {
            $rf = $v;
        });

        $p4->reject('xxx2');
        $this->assertEquals(false, $p3->pended());

        $this->assertEquals('vvv1', $rs);
        $this->assertEquals('xxx2', $rf);

        $p1 = $p2 = $p3 = $p4 = null;

        $race1 = $race2 = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testFeatureAll()
    {
        $test = $this;

        $r1 = $r2 = $r3 = null;

        $p1 = Promise::deferred();
        $p2 = Promise::deferred();

        $all1 = Promise::all($p1, $p2);

        $all1->then(function ($rvs) use (&$r1) {
            $r1 = $rvs;
        });

        $p1->resolve('aaa');
        $this->assertEquals(null, $r1);
        $p2->resolve('bbb');
        $this->assertEquals(['aaa', 'bbb'], $r1);

        $p3 = Promise::deferred();
        $p4 = Promise::deferred();

        $all2 = Promise::all($p3, $p4);

        $all2->then(function () use ($test) {
            $test->assertFalse(true);
        }, function ($v) use (&$r2) {
            $r2 = $v;
        });

        $p4->reject('kkk');
        $this->assertFalse($p3->pended());

        $this->assertEquals('kkk', $r2);

        $p5 = Promise::deferred();
        $p6 = Promise::deferred();

        $p5->then(function () use (&$r3) {
            $r3 = 'aaa';
        }, function () use (&$r3) {
            $r3 = 'zzz';
        });
        $p6->then(function () use (&$r3) {
            $r3 = 'sss';
        }, function () use (&$r3) {
            $r3 = 'xxx';
        });

        $all3 = Promise::all($p5, $p6);

        $this->assertEquals(null, $r3);

        $all3->reject();

        $this->assertEquals('xxx', $r3);

        $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = null;

        $all1 = $all2 = $all3 = null;

        $this->assertEquals(0, Stats::pending());
    }

    public function testFeatureSettled()
    {
        $em = '';

        try {
            Promise::rejected(new \Exception('test222'))->catch(function (\Throwable $e) use (&$em) {
                $em = 'sad';
            });
        } catch (\Throwable $e) {
            $em = $e->getMessage();
        }


        $this->assertEquals('sad', $em);

        $this->assertEquals(0, Stats::pending());
    }

    public function testFeatureFusion()
    {
        $p1 = Promise::rejected(new \Exception('test333'));

        $em1 = '';

        try {
            $p1->fusion();
        } catch (\Throwable $e) {
            $em1 = $e->getMessage();
        }

        $this->assertEquals('test333', $em1);

        $em2 = $em3 = '';

        $pa1 = Promise::resolved();
        $pa2 = Promise::rejected(new \Exception('test123'));

        $p3 = Promise::deferred();
        $p4 = Promise::all($pa1, $pa2)->sync($p3);

        try {
            $p3->fusion();
        } catch (\Throwable $e) {
            $em2 = $e->getMessage();
        }

        try {
            $p4->fusion();
        } catch (\Throwable $e) {
            $em3 = $e->getMessage();
        }

        $this->assertEquals('test123', $em2);
        $this->assertEquals('test123', $em3);

        $p1 = $pa1 = $pa2 = $p3 = $p4 = null;

        $this->assertEquals(0, Stats::pending());
    }
}
