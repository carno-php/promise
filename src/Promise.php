<?php
/**
 * Promise EXPR
 * User: moyo
 * Date: 03/08/2017
 * Time: 11:54 AM
 */

namespace Carno\Promise;

use Carno\Promise\Exception\InvalidState;
use Carno\Promise\Features\All;
use Carno\Promise\Features\Deferred;
use Carno\Promise\Features\Fusion;
use Carno\Promise\Features\Race;
use Carno\Promise\Features\Settled;
use Carno\Promise\Features\Sync;
use Closure;
use SplStack;
use Throwable;
use TypeError;

final class Promise implements Promised
{
    use All, Deferred, Race, Settled, Sync, Fusion;

    /**
     * PENDING is initial state
     */
    private const PENDING = 0xF0;
    private const FULFILLED = 0xF1;
    private const REJECTED = 0xF2;

    /**
     * @var int
     */
    private $state = self::PENDING;

    /**
     * @var bool
     */
    private $ack = false;

    /**
     * @var SplStack
     */
    private $stack = null;

    /**
     * @var bool
     */
    private $chained = false;

    /**
     * @var mixed
     */
    private $result = null;

    /**
     * Promise constructor.
     * @param callable $executor
     */
    public function __construct(?callable $executor)
    {
        Stats::proposed($this);
        $this->stack = new SplStack;
        $executor && $this->calling($executor, $this, [$this]);
    }

    /**
     */
    public function __destruct()
    {
        Stats::confirmed($this);
    }

    /**
     * @return bool
     */
    public function pended() : bool
    {
        return ! $this->ack;
    }

    /**
     * @return bool
     */
    public function chained() : bool
    {
        return $this->chained;
    }

    /**
     * @param callable $onFulfilled
     * @param callable $onRejected
     * @return Promised
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null) : Promised
    {
        $this->chained = true;

        $next = new self(null);

        $this->stack->push([$onFulfilled, $onRejected, $next]);

        $this->pended() || $this->settle();

        return $next;
    }

    /**
     * @param callable $onThrowing
     * @return Promised
     */
    public function catch(callable $onThrowing) : Promised
    {
        return $this->then(null, $onThrowing);
    }

    /**
     * @param mixed ...$args
     */
    public function resolve(...$args) : void
    {
        $this->settle(self::FULFILLED, $args);
    }

    /**
     * @param mixed ...$args
     * @throws Throwable
     */
    public function reject(...$args) : void
    {
        $this->settle(self::REJECTED, $args);
    }

    /**
     * @param Throwable $error
     * @throws Throwable
     */
    public function throw(Throwable $error) : void
    {
        $this->reject($error);
    }

    /**
     * @param callable $executor
     * @param Promised $current
     * @param array $arguments
     * @return array [result:mixed, error:throwable]
     */
    private function calling(callable $executor, Promised $current, array $arguments) : array
    {
        try {
            return [call_user_func($executor, ...$arguments), null];
        } catch (Throwable $error) {
            $current->pended() && $current->throw($error);
            return [null, $error];
        }
    }

    /**
     * @param Promised $promised
     * @param int $type
     * @return Closure
     */
    private function resolver(Promised $promised, int $type) : Closure
    {
        return static function (...$args) use ($promised, $type) {
            $type === self::FULFILLED
                ? $promised->resolve(...$args)
                : $promised->reject(...$args)
            ;
        };
    }

    /**
     * @param int $state
     * @param array $args
     * @return Promised
     * @throws Throwable
     * @throws TypeError
     */
    private function settle(int $state = null, array $args = []) : Promised
    {
        if (is_null($state)) {
            $result = $this->result;
        } else {
            if ($this->ack) {
                throw new InvalidState('Promise is already confirmed');
            }
            $this->ack = true;
            $this->state = $state;
            $this->result = $result = $args;
        }

        $resolved = $this->state === self::FULFILLED;

        /**
         * @var Promised $next
         */

        while (!$this->stack->isEmpty()) {
            list($onFulfilled, $onRejected, $next) = $this->stack->shift();

            if (null === $executor = $resolved ? $onFulfilled : $onRejected) {
                $resolved ? $next->resolve(...$result) : $next->reject(...$result);
                continue;
            }

            list($stash, $failure) = $this->calling($executor, $next, $result);

            if ($failure) {
                if ($this->fusion || !$next->chained()) {
                    throw $failure;
                }
            } elseif ($stash instanceof Promised) {
                if ($stash === $this) {
                    throw new TypeError('Response promise is same with current');
                } else {
                    $stash->then($this->resolver($next, self::FULFILLED), $this->resolver($next, self::REJECTED));
                }
            } elseif ($stash instanceof Closure) {
                $this->calling($stash, $next, [$next]);
            } else {
                $resolved ? $next->resolve($stash) : $next->reject($stash);
            }
        }

        return $this;
    }
}
