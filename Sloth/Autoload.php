<?php
class Sloth_Autoload
{
    static $slothPath = null;
    static $limit = true;

    public static function autoload($sClass)
    {
        if (self::$limit && strpos($sClass, 'Sloth_') !== 0) return;
        if (is_null(self::$slothPath)) {
            self::$slothPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        }
        $sPath = self::$slothPath . strtr($sClass, array('_' => DIRECTORY_SEPARATOR)).'.php';
        is_file($sPath) and include($sPath);
    }

    public static function register($limit = true)
    {
        self::$limit = $limit;
        spl_autoload_register('Sloth_Autoload::autoload');
        if (function_exists('__autoload')) {
            spl_autoload_unregister('__autoload');
            spl_autoload_register('__autoload');
        }
    }
    
    public static function unregister()
    {
        spl_autoload_unregister('Sloth_Autoload::autoload');
    }
}

Sloth_Autoload::register();

class Sloth
{
    /**
     * fn
     * @param string $sExp
     * @return callback
     */
    public static function fn($sExp)
    {
        $sArg = '';
        if (preg_match_all('/\$\w+/', $sExp, $matches)) {
            $sArg = implode(',', array_unique($matches[0]));
        }
        return create_function($sArg, 'return '.$sExp.';');
    }

    /**
     * op
     * @param string $sOperator
     * @return callback
     */
    public static function op($sOperator)
    {
        return create_function('$a,$b', 'return $a'.$sOperator.'$b;');
    }

    /**
     * iter
     * @param mixed $mFirst
     * @param func $fnCallback
     * @return object Sloth_Iterator
     */
    public static function iter($mFirst, $fnCallback = null)
    {
        if (is_null($fnCallback)) {
            if (is_array($mFirst)) {
                return new Sloth_Iterator($mFirst);
            } else if ($mFirst instanceof Sloth_IteratorInterface) {
                return $mFirst;
            } else if ($mFirst instanceof Iterator) {
                return new Sloth_Iterator($mFirst);
            } else if ($mFirst instanceof IteratorAggregate) {
                $iter = $mFirst->getIterator();
                if ($iter instanceof Sloth_IteratorInterface) {
                    return $iter;
                } else if ($iter instanceof Iterator) {
                    return new Sloth_Iterator($iter);
                }
            } else {
                throw new InvalidArgumentException;
            }
        } else if (is_int($fnCallback)) {
            return Sloth::ref(new Sloth_Follow($mFirst, Sloth::fn('$x+1')))->take($fnCallback);
        } else {
            return new Sloth_Follow($mFirst, $fnCallback);
        }
    }

    /**
     * iterAll
     * @param object $obj
     * @return object
     */
    public static function iterAll($aSeq)
    {
        return new Sloth_Append(null, $aSeq);
    }

    /**
     * ref
     * @param object $obj
     * @return object
     */
    public static function ref($obj)
    {
        return $obj;
    }

    /**
     * doctest
     * @param string $sFile
     * @return void
     */
    public static function doctest($sFile, $bQuiet = false)
    {
        $oTest = new Sloth_DocTest($sFile, $bQuiet);
        $oTest->invoke();
    }
}

if (!function_exists('fn')) {
    function fn($sExp) { return Sloth::fn($sExp); }
}
if (!function_exists('op')) {
    function op($sOp) { return Sloth::op($sOp); }
}
if (!function_exists('iter')) {
    function iter($fst, $fn = null) { return Sloth::iter($fst, $fn); }
}
if (!function_exists('iterAll')) {
    function iterAll($seq) { return Sloth::iterAll($seq); }
}
if (!function_exists('ref')) {
    function ref($o) { return Sloth::ref($o); }
}
if (!function_exists('doctest')) {
    function doctest($file,$flag=false) { return Sloth::doctest($file,$flag); }
}

//
// DocTest
//
!count(debug_backtrace()) and Sloth::doctest(__FILE__);

