<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Limit
 * >>>>
 * // make infinite sequence.
 * $seq = iter(1, fn('$n+1'));
 * // slice infinite sequence.
 * foreach ($seq->slice(10, 20) as $elt) {
 *     echo "{$elt},";
 * }
 * echo "\n";
 *|11,12,13,14,15,16,17,18,19,20,
 * <<<<
 */
class Sloth_Limit extends Sloth_Iterator
{
    public function __construct($seq, $offset = 0, $count = -1)
    {
        $iterator = Sloth::iter($seq);
        $itLimit = new LimitIterator($iterator, $offset, $count);
        $this->iterator = $itLimit;
    }
}
//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

