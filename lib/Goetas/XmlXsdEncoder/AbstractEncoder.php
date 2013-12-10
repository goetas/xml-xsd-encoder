<?php
namespace Goetas\XmlXsdEncoder;

abstract class AbstractEncoder
{
    private static $refCacheProp = array();
    private static $refCacheObj = array();

    protected static function getValueFrom($variabile, $index)
    {
        if (is_array($variabile) && isset($variabile[$index])) {
            return $variabile[$index];
        } elseif (is_object($variabile)) {
            $index = self::camelize($index);

            return self::objectExtract($variabile, $index)->getValue($variabile);
        }
    }
    protected static function tryGetValueFrom($variabile, $index)
    {
        try {
            return self::getValueFrom($variabile, $index);
        } catch (\ReflectionException $e) {
            return null;
        }
    }
    protected static function setValueTo(&$variabile, $index, $value)
    {
        if (is_array($variabile)) {
            $variabile[$index] = $value;
        } elseif ($variabile instanceof \stdClass) {
            $variabile->$index =  $value;
        } elseif (is_object($variabile)) {
            $index = self::camelize($index);
            self::objectExtract($variabile, $index)->setValue($variabile, $value);
        }
    }
    protected static function addValueTo(&$variabile, $value)
    {
        if (is_array($variabile) || $variabile instanceof \ArrayAccess) {
            $variabile[] = $value;
        }
    }
    protected static function objectExtract($obj, $name)
    {
        $c = get_class($obj);

        if (!isset(self::$refCacheProp[$c][$name])) {

            if (!isset(self::$refCacheObj[$c])) {
                self::$refCacheObj[$c] = new \ReflectionObject($obj);
            }
            try {
                self::$refCacheProp[$c][$name] =  self::$refCacheObj[$c]->getProperty($name);
                self::$refCacheProp[$c][$name]->setAccessible(true);
            } catch (\ReflectionException $e) {
                throw $e;
            }
        }

        return self::$refCacheProp[$c][$name];
    }

    public static function classify($word)
    {
        return str_replace(" ", "", ucwords(strtr($word, "_-", "  ")));
    }

    /**
     * Camelize a word. This uses the classify() method and turns the first character to lowercase
     *
     * @param  string $word
     * @return string $word
     */
    public static function camelize($word)
    {
        if ($word=="__value") {
            return $word;
        }

        return lcfirst(self::classify($word));
    }

}
