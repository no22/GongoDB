<?php
!count(debug_backtrace()) and require "./AutoLoad.php";
/**
 * Sloth_Append
 * >>>>
 * $seq = iter(array(1,1,1,1,1))->cat(array(2,2,2));
 * foreach ($seq as $elt) {
 *     echo "{$elt},";
 * }
 * echo "\n";
 *|1,1,1,1,1,2,2,2,
 * <<<<
 */
class Sloth_Append extends Sloth_Iterator
{
    public function __construct($seq,$aSeq)
    {
        $iterator = null;
        $itAppend = new AppendIterator();
        if (!is_null($seq)) {
            $iterator = Sloth::iter($seq);
            $itAppend->append($iterator);
        }
        foreach ($aSeq as $mSeq) {
            $itAppend->append(Sloth::iter($mSeq));
        }
        $this->iterator = $itAppend;
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);
