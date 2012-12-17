<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_While
 * >>>>
 * $seq = iter(1,fn('$n+1'))->map(fn('$n*$n'))->dropWhile(fn('$x<38'));
 * foreach ($seq->take(10) as $elt) {
 *     echo "{$elt}\n";
 * }
 *|49
 *|64
 *|81
 *|100
 *|121
 *|144
 *|169
 *|196
 *|225
 *|256
 * <<<<
 */
class Sloth_DoWhile extends Sloth_Iterator
{
    protected $callback;
    protected $isDrop = false;
    
    function __construct($seq, $callback, $drop = false)
    {
        parent::__construct($seq);
        if (!is_callable($callback)) throw new InvalidArgumentException;
        $this->callback = $callback;
        $this->isDrop = $drop;
    }

    public function rewind()
    {
        $this->iterator->rewind();
        if ($this->isDrop) {
            while (call_user_func($this->callback, $this->current()) && $this->valid()) {
                $this->next();
            }
        }
    }
    
    public function valid()
    {
        if ($this->isDrop) {
            return $this->iterator->valid();
        } else {
            return call_user_func($this->callback, $this->iterator->current());
        }
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);
