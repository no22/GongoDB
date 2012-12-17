<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Filter
 * >>>>
 * $seq = iter(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10))->filter(fn('$n%2===0'));
 * foreach ($seq as $elt) {
 *     echo "{$elt},";
 * }
 * echo "\n";
 *|2,4,6,8,10,
 * <<<<
 */
class Sloth_CallbackFilterIterator extends FilterIterator
{
    protected $callback;

    public function __construct($iterator, $callback)
    {
        parent::__construct($iterator);
        if (!is_callable($callback)) throw new InvalidArgumentException;
        $this->callback = $callback;
    }

    public function __clone()
    {
        parent::__clone();
        $this->callback = clone $this->callback;
    }

    public function accept()
    {
        return call_user_func($this->callback, $this->getInnerIterator()->current());
    }
}

class Sloth_Filter extends Sloth_Iterator
{
    public function __construct($seq, $callback)
    {
        $iterator = Sloth::iter($seq);
        $itFilter = new Sloth_CallbackFilterIterator($iterator, $callback);
        $this->iterator = $itFilter;
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

