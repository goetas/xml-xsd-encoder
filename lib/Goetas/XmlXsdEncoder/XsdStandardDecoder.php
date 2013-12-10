<?php
namespace Goetas\XmlXsdEncoder;

use goetas\webservices\bindings\soap\DecoderInterface;

use goetas\xml\xsd\Type;

class XsdStandardDecoder implements DecoderInterface
{
    protected $fromMap = array();
    public function __construct()
    {
        $simpleFromStr = function ($node) {
            return strval($node->nodeValue);
        };
        $simpleFromBool = function ($node) {
            return strval($node->nodeValue)=='true';
        };
        $simpleFromInt = function ($node) {
            return intval($node->nodeValue);
        };
        $simpleFromFloat = function ($node) {
            return floatval($node->nodeValue);
        };
        $simpleFromDate = function ($node) {
            return new \DateTime($node->nodeValue);
        };

        $xsd = "http://www.w3.org/2001/XMLSchema";

        $this->fromMap[$xsd]["int"] = $simpleFromInt;
        $this->fromMap[$xsd]["integer"] = $simpleFromInt;
        $this->fromMap[$xsd]["short"] = $simpleFromInt;
        $this->fromMap[$xsd]["long"] = $simpleFromInt;

        $this->fromMap[$xsd]["double"] = $simpleFromFloat;
        $this->fromMap[$xsd]["float"] = $simpleFromFloat;
        $this->fromMap[$xsd]["decimal"] = $simpleFromFloat;

        $this->fromMap[$xsd]["string"] = $simpleFromStr;
        $this->fromMap[$xsd]["anyURI"] = $simpleFromStr;
        $this->fromMap[$xsd]["QName"] = $simpleFromStr;
        $this->fromMap[$xsd]["base64Binary"] = $simpleFromStr;

        $this->fromMap[$xsd]["boolean"] = $simpleFromBool;

        $this->fromMap[$xsd]["gYear"] = $simpleFromInt;

        $this->fromMap[$xsd]["dateTime"] = $simpleFromDate;
        $this->fromMap[$xsd]["date"] = $simpleFromDate;
        $this->fromMap[$xsd]["time"] = $simpleFromDate;

    }
    public function supports(Type $type)
    {
        return isset($this->fromMap[$type->getNs()][$type->getName()]);
    }
    public function decode(\DOMNode $node, Type $type)
    {
        return call_user_func($this->fromMap[$type->getNs()][$type->getName()], $node, $type);
    }
}
