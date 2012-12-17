<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Follow
 * >>>>
 * $iter = iter(0, fn('$n+1'));
 * $iter->take(10)->each(fn('print($e.",")'));
 * echo "\n";
 *|0,1,2,3,4,5,6,7,8,9,
 * <<<<
 */
class Sloth_Follow extends Sloth_Iterator
{
    protected $first, $fn, $key = 0, $current_value, $is_valid = true;

    function __construct($first, $fn)
    {
        list($this->first, $this->fn) = func_get_args();
        $this->current_value = $first;
        parent::__construct($this);
    }
    
    public function __clone()
    {
        parent::__clone();
        $this->first = clone $this->first;
        $this->current_value = clone $this->current_value;
    }

    function valid()
    {
        return $this->is_valid;
    }
    
    function next()
    {
        $result = call_user_func($this->fn, $this->current_value, ++$this->key);
        if ($result === false) {
            $this->is_valid = false;
        } else {
            $this->current_value = $result;
        }
    }
    
    function current() {
        return $this->current_value;
    }
    
    function key() {
        return $this->key;
    }
    
    function rewind()
    {
        $this->key = 0;
        $this->current_value = $this->first;
        $this->is_valid = true;
    }
    
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

