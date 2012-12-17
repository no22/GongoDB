<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Cycle
 * >>>>
 * $seq = iter(array(1,2,3))->cycle();
 * foreach ($seq->take(10) as $elt) {
 *     echo "{$elt},";
 * }
 * echo "\n";
 *|1,2,3,1,2,3,1,2,3,1,
 * <<<<
 */
class Sloth_Cycle extends Sloth_Iterator
{
    public function __construct($seq)
    {
        $iterator = Sloth::iter($seq);
        $itInfin = new InfiniteIterator($iterator);
        $this->iterator = $itInfin;
    }
}
//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

