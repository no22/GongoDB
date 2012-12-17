<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Scan
 * >>>>
 * $seq = array(1, 2, 3, 4, 5);
 * // lazy scan
 * foreach (iter($seq)->scan(1, op('*')) as $elt) {
 *     echo "{$elt}\n";
 * }
 *|1
 *|2
 *|6
 *|24
 *|120
 * <<<<
 */
class Sloth_Scan extends Sloth_Iterator
{
    protected $callback;
    protected $initialValue;
    protected $acumulator;
    protected $isCached = false;
    
    function __construct($seq, $init, $callback)
    {
        parent::__construct($seq);
        if (!is_callable($callback)) throw new InvalidArgumentException;
        $this->callback = $callback;
        $this->initialValue = $init;
        $this->accumulator = $init;
        $this->isCached = false;
    }

    public function __clone()
    {
        parent::__clone();
        if (is_object($this->initialValue)) {
            $this->initialValue = clone $this->initialValue;
        }
    }

    function current() 
    {
        if (!$this->isCached) {
            $this->accumulator = call_user_func($this->callback, $this->accumulator, $this->iterator->current());
            $this->isCached = true;
        }
        return $this->accumulator;
    }
    
    function rewind()
    {
        parent::rewind();
        $this->acumulator = $this->initialValue;
        $this->isCached = false;
    }

    function next()
    {
        $this->isCached = false;
        parent::next();
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

