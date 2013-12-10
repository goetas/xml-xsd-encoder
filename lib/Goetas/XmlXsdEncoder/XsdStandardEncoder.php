<?php
namespace Goetas\XmlXsdEncoder;

use goetas\webservices\bindings\soap\EncoderInterface;

use goetas\xml\xsd\Type;

class XsdStandardEncoder implements EncoderInterface
{
    protected $toMap = array();

    public function __construct()
    {
        $simpleToStr = function ($data) {
            return strval($data);
        };
        $simpleToInt = function ($data) {
            if (is_object($data)) {
                try {
                    $data = strval($data);
                } catch (\Exception $e) {
                    throw new \Exception("Can not convert object of type ".get_class($data)." to integer");
                }
            }

            return intval($data);
        };
        $simpleToFloat = function ($data) {
            return number_format($data, 20, ".", "");
        };

        $simpleToBool = function ($data) {
            return $data?'true':'false';
        };
        $simpleToDecimal = function ($data) {
            return number_format(round($data,2), 2,'.','');
        };
        $simpleToDate = function ($format) {
            return function ($data) use ($format) {
                if ($data instanceof \DateTime) {
                    return $data->format($format);
                } elseif (is_numeric($data)) {
                    return date($format, $data);
                }
            };
        };

        $xsd = "http://www.w3.org/2001/XMLSchema";

        $this->toMap[$xsd]["int"] = $simpleToInt;
        $this->toMap[$xsd]["integer"] = $simpleToInt;
        $this->toMap[$xsd]["long"] = $simpleToInt;
        $this->toMap[$xsd]["short"] = $simpleToInt;

        $this->toMap[$xsd]["double"] = $simpleToFloat;
        $this->toMap[$xsd]["float"] = $simpleToFloat;
        $this->toMap[$xsd]["decimal"] = $simpleToInt;

        $this->toMap[$xsd]["string"] = $simpleToStr;
        $this->toMap[$xsd]["base64Binary"] = $simpleToStr;
        $this->toMap[$xsd]["anyURI"] = $simpleToStr;

        $this->toMap[$xsd]["boolean"] = $simpleToBool;

        $this->toMap[$xsd]["gYear"] = $simpleToInt;

        $this->toMap[$xsd]["dateTime"] = $simpleToDate(DATE_W3C);
        $this->toMap[$xsd]["date"] = $simpleToDate("Y-m-d");
        $this->toMap[$xsd]["time"] = $simpleToDate("H:i:s");
    }
    public function supports(Type $type)
    {
        return isset($this->toMap[$type->getNs()][$type->getName()]);
    }
    public function encode($variable, \DOMNode $node, Type $type)
    {
        $value = call_user_func($this->toMap[$type->getNs()][$type->getName()], $variable, $node, $type);
        if ($node instanceof \DOMAttr || !in_array($type->getName(), array("string"))) {
            $valueNode = $node->ownerDocument->createTextnode($value);
        } else {
            $valueNode = $node->ownerDocument->createCDATASection($value);
        }
        $node->appendChild($valueNode);
    }
}
