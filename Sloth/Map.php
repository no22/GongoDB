<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Map
 * >>>>
 * function hoge($val)
 * {
 *     echo "called\n";
 *     return $val * $val;
 * }
 * 
 * $seq = array(1, 2, 3, 4, 5);
 * 
 * // array_map
 * foreach (array_map('hoge', $seq) as $elt) {
 *     echo "{$elt}\n";
 * }
 * echo "\n";
 * 
 * // lazy map
 * foreach (iter($seq)->map('hoge') as $elt) {
 *     echo "{$elt}\n";
 * }
 *|called
 *|called
 *|called
 *|called
 *|called
 *|1
 *|4
 *|9
 *|16
 *|25
 *|
 *|called
 *|1
 *|called
 *|4
 *|called
 *|9
 *|called
 *|16
 *|called
 *|25
 * <<<<
 */
class Sloth_Map extends Sloth_Iterator
{
    protected $callback;
    
    function __construct($seq, $callback)
    {
        parent::__construct($seq);
        if (!is_callable($callback)) throw new InvalidArgumentException;
        $this->callback = $callback;
    }
    
    function current()
    {
        return call_user_func($this->callback, $this->iterator->current());
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

