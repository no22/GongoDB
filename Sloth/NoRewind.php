<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

class Sloth_NoRewind extends Sloth_Iterator
{
    public function __construct($seq)
    {
        $itNoRewind = new NoRewindIterator(Sloth::iter($seq));
        $this->iterator = $itNoRewind;
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);
