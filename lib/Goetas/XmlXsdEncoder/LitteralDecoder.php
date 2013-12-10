<?php
namespace Goetas\XmlXsdEncoder;

use goetas\webservices\bindings\soap\DecoderInterface;

use goetas\webservices\exceptions\ConversionNotFoundException;

use goetas\webservices\bindings\soap\MessageComposer;

use goetas\xml\xsd\SimpleContent;

use goetas\xml\xsd\ComplexType;

use goetas\xml\xsd\SimpleType;

use goetas\xml\xsd\AbstractComplexType;

use goetas\xml\xsd\Type;

class LitteralDecoder extends AbstractEncoder implements DecoderInterface
{
    /**
     *
     * @var \goetas\webservices\bindings\soap\MessageComposer
     */
    protected $composer;

    public function __construct(MessageComposer $composer)
    {
        $this->composer = $composer;
    }

    public function supports(Type $type)
    {
        return ($type instanceof ComplexType);
    }
    public function decode(\DOMNode $node, Type $type)
    {
        if ($type instanceof AbstractComplexType || $type instanceof SimpleType) {
            $variabile = $this->convertSimpleXmlPhp($node, $type);
            $this->decodeInto($node, $type , $variabile);
        } else {
            $variabile = $node;
        }

        return $variabile;
    }
    public function decodeInto(\DOMNode $node, Type $type , $variabile)
    {
        if ($type instanceof AbstractComplexType) {

            if ($type instanceof SimpleContent && $variabile instanceof \stdClass) { // hack per i complex type simple content
                $newVariabile = new \stdClass();
                $newVariabile->_ = $variabile;
                $variabile = $newVariabile;
            } elseif ($type instanceof SimpleContent && $type->getBase()) {
                $this->setBaseValue($variabile, $this->convertSimpleXmlPhp($node, $type->getBase()));
            }

            foreach ($type->getAttributes() as $attribute) {
                if ($attribute->getQualification()=="qualified") {
                    $attributeNode = $node->getAttributeNodeNS($attribute->getNs(), $attribute->getName());
                } else {
                    $attributeNode = $node->getAttributeNode($attribute->getName());
                }

                if ($attributeNode) {
                    self::setValueTo($variabile, $attribute->getName(), $this->convertSimpleXmlPhp($attributeNode, $attribute->getType()));
                } elseif ($attribute->isRequred()) {
                    throw new \Exception("Noon trovo l'attributo obbligatorio $attribute su $type");
                }
            }

            if ($type instanceof ComplexType) {

                $childs = array();
                foreach ($node->childNodes as $child) {
                    $childs[$child->namespaceURI][$child->localName][]=$child;
                }
                foreach ($type->getElements() as $element) {

                    $elementType = $element->getType();

                    $ns = $element->getQualification()=="qualified"?$element->getNs():"";
                    $nm = $element->getName();

                    if (isset($childs[$ns][$nm])) {
                        if ($element->getMax()>1) {
                            foreach ($childs[$ns][$nm] as $elementNode) {
                                self::addValueTo($variabile, $this->convertSimpleXmlPhp($elementNode, $elementType ));
                            }
                        } else {
                            $elementNode = array_shift($childs[$ns][$nm]);
                            $value = $this->convertSimpleXmlPhp($elementNode, $elementType );
                            if ($value instanceof \stdClass && is_object($variabile) && !($variabile instanceof \stdClass ) ) {
                                throw new \Exception("Non trovo nessuna conversione valida per tag per il tipo {{$elementType->getNs()}}#{$elementType->getName()}");
                            }
                            self::setValueTo($variabile, $element->getName(), $value);
                        }
                    } elseif ($element->getMin()>0) {
                        throw new \Exception("Non trovo nessun tag per l'elemento di tipo {{$ns}}#{$nm}");
                    }
                }

                if ($type->getBase()) {
                    $this->decodeInto($node, $type->getBase(), $variabile);
                }
            }
        } elseif ($type instanceof SimpleType) {
            if (is_object($variabile) && $type->getBase()) {
                $this->setBaseValue($variabile, $this->convertSimpleXmlPhp($node, $type->getBase()));
            }
        }
    }
    protected function setBaseValue($into, $value)
    {
        self::setValueTo($into, '__value', $into);
    }
    protected function convertSimpleXmlPhp(\DOMNode $node, Type $xsd)
    {
        try {
            return $this->composer->decode($node, $xsd);
        } catch (ConversionNotFoundException $e) {
            $base = $xsd->getBase();
            if ($base) {
                return $this->convertSimplePhpXml($node, $base);
            } else {
                throw $e;
            }
        }
    }
}
