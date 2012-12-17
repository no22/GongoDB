<?php
!count(debug_backtrace()) and require "./AutoLoad.php";
/**
 * Sloth_Chunk
 * >>>>
 * $iter = iter(array(1,2,3,4,5,6,7,8,9))->chunk(3);
 * foreach($iter as $it) {
 *     foreach($it as $n) {
 *         echo $n . ' ';
 *     }
 *     echo "\n";
 * }
 *|1 2 3 
 *|4 5 6 
 *|7 8 9 
 * <<<<
 */
class Sloth_Chunk extends Sloth_Iterator
{
    protected $size = null;
    
    function __construct($seq, $size)
    {
        parent::__construct($seq);
        $this->size = $size;
    }

    public function current()
    {
        return new Sloth_Limit(new NoRewindIterator($this->iterator), 0, $this->size);
    }
    
    public function next()
    {
        // do nothing
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);
