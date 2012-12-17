<?php
!count(debug_backtrace()) and require "./AutoLoad.php";

/**
 * Sloth_Iterator
 * >>>>
 * foreach (iter(array(1,2,3,4,5,6)) as $elt) {
 *     echo "{$elt},";
 * }
 * echo "\n";
 *|1,2,3,4,5,6,
 * <<<<
 */
class Sloth_Iterator implements Iterator, Sloth_IteratorInterface
{
    protected $iterator;

    /**
     * __construct
     * @param mixed $seq
     */
    public function __construct($seq)
    {
        if (is_array($seq)) $this->iterator = new ArrayIterator($seq);
        elseif ($seq instanceof Iterator) $this->iterator = $seq;
        elseif ($seq instanceof IteratorAggregate) $this->iterator = $seq->getIterator();
        else throw new InvalidArgumentException;
    }

    public function __clone()
    {
        $this->iterator = clone $this->iterator;
    }
    
    public function current()
    {
        return $this->iterator->current();
    }
    
    public function next()
    {
        $this->iterator->next();
    }
    
    public function valid()
    {
        return $this->iterator->valid();
    }
    
    public function key()
    {
        return $this->iterator->key();
    }
    
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * first
     * current first element
     * @param  void
     * @return mixed
     */
    public function first()
    {
        return $this->valid() ? $this->current() : null ;
    }

    /**
     * rest
     * current rest iterator (clone)
     * @param  void
     * @return Iterator
     */
    public function rest()
    {
        return $this->drop(1);
    }

    /**
     * each
     * CAUTION! each method is not LAZY!
     * @param func $fnCallback
     * @return void
     */
    public function each($fnCallback)
    {
        if (!is_callable($fnCallback)) throw new InvalidArgumentException;
        foreach($this->iterator as $value) {
            call_user_func($fnCallback, $value);
        }
    }

    /**
     * eachWithIndex
     * CAUTION! eachWithIndex method is not LAZY!
     * @param func $fnCallback
     * @return void
     */
    public function eachWithIndex($fnCallback)
    {
        if (!is_callable($fnCallback)) throw new InvalidArgumentException;
        foreach($this->iterator as $key => $value) {
            call_user_func($fnCallback, $value,$key);
        }
    }

    /**
     * reduce
     * CAUTION! reduce method is not LAZY!
     * @param func $fnCallback
     * @param mixed $mInit
     * @return mixed
     */
    public function reduce($fnCallback, $mInit = null)
    {
        if (!is_callable($fnCallback)) throw new InvalidArgumentException;
        $mAcc = $mInit;
        foreach($this->iterator as $value) {
            $mAcc = call_user_func($fnCallback, $mAcc, $value);
        }
        return $mAcc;
    }

    /**
     * map
     * Lazy Map methods
     * @param func $fnCallback
     * @return object Iterator
     */
    public function map($fnCallback)
    {
        return new Sloth_Map($this, $fnCallback);
    }

    /**
     * filter
     * Lazy Filter method
     * @param func $fnCallback
     * @return object Iterator
     */
    public function filter($fnCallback)
    {
        return new Sloth_Filter($this, $fnCallback);
    }

    /**
     * take
     * Lazy Take method
     * @param int $iCount
     * @return object Iterator
     */
    public function take($iCount)
    {
        return new Sloth_Limit($this, 0, $iCount);
    }
    
    /**
     * drop
     * Lazy Drop method
     * @param int $iCount
     * @return object Iterator
     */
    public function drop($iCount)
    {
        return new Sloth_Limit($this, $iCount);
    }

    /**
     * slice
     * Lazy Slice method
     * @param int $iBegin
     * @param int $iEnd
     * @return object Iterator
     */
    public function slice($iBegin,$iEnd)
    {
        return new Sloth_Limit($this, $iBegin, $iEnd - $iBegin);
    }

    /**
     * cycle
     * Lazy Cycle method
     * @return object Iterator
     */
    public function cycle()
    {
        return new Sloth_Cycle($this);
    }

    /**
     * takeWhile
     * Lazy takeWhile method
     * @param func $fnCallback
     * @return object Iterator
     */
    public function takeWhile($fnCallback)
    {
        return new Sloth_DoWhile($this, $fnCallback, false);
    }

    /**
     * dropWhile
     * Lazy dropWhile method
     * @param func $fnCallback
     * @return object Iterator
     */
    public function dropWhile($fnCallback)
    {
        return new Sloth_DoWhile($this, $fnCallback, true);
    }

    /**
     * zip
     * Lazy Zip method
     * @param array $seq
     * @return object Iterator
     */
    public function zip($seq)
    {
        $aSeq = func_get_args();
        return new Sloth_Zip($this, $aSeq);
    }

    /**
     * zipArray
     * Lazy Zip method
     * @param array $seq
     * @return object Iterator
     */
    public function zipArray($aSeq)
    {
        return new Sloth_Zip($this, $aSeq);
    }

    /**
     * zipWith
     * Lazy ZipWith method
     * @param func $fnCallback
     * @param array $seq
     * @return object Iterator
     */
    public function zipWith($fnCallback, $seq)
    {
        $aSeq = func_get_args();
        array_shift($aSeq);
        return new Sloth_Zip($this, $aSeq, $fnCallback);
    }

    /**
     * zipArrayWith
     * Lazy ZipWith method
     * @param func $fnCallback
     * @param array $seq
     * @return object Iterator
     */
    public function zipArrayWith($fnCallback, $aSeq)
    {
        return new Sloth_Zip($this, $aSeq, $fnCallback);
    }

    /**
     * cat
     * Lazy Concat method
     * @param mixed $seq
     * @return object Iterator
     */
    public function cat($seq)
    {
        $aSeq = func_get_args() ;
        return new Sloth_Append($this, $aSeq);
    }

    /**
     * catArray
     * Lazy Concat method
     * @param mixed $seq
     * @return object Iterator
     */
    public function catArray($aSeq)
    {
        return new Sloth_Append($this, $aSeq);
    }

    /**
     * chunk
     * Lazy Chunk method
     * @param int $iSize
     * @return object Iterator
     */
    public function chunk($iSize)
    {
        return new Sloth_Chunk($this, $iSize);
    }

    /**
     * scan
     * Lazy scan method
     * @param mixed $mInit
     * @param func $fnCallback
     * @return object Iterator
     */
    public function scan($mInit, $fnCallback)
    {
        return new Sloth_Scan($this, $mInit, $fnCallback);
    }

    /**
     * toArray
     * Convert to array
     * @param  bool $useKeys = true
     * @return array
     */
    public function toArray($useKeys = true)
    {
        return iterator_to_array($this, $useKeys);
    }

    /**
     * dup
     * @param  void
     * @return object Iterator
     */
    public function dup()
    {
        return clone $this;
    }

    /**
     * norewind
     * 
     * @param  void
     * @return object Iterator
     */
    public function norewind()
    {
        return new Sloth_NoRewind($this);
    }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

