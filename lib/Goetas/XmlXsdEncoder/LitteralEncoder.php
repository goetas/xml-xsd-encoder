<?php
namespace Goetas\XmlXsdEncoder;

use goetas\webservices\bindings\soap\EncoderInterface;

use goetas\webservices\exceptions\ConversionNotFoundException;

use goetas\webservices\bindings\soap\MessageComposer;

use goetas\xml\xsd\ComplexType;

use goetas\xml\xsd\SimpleType;

use goetas\xml\xsd\AbstractComplexType;

use goetas\xml\xsd\Type;

class LitteralEncoder extends AbstractEncoder implements EncoderInterface
{
    /**
     *
     * @var \goetas\webservices\bindings\soap\MessageComposer
     */
    protected $composer;
    const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public function __construct(MessageComposer $composer)
    {
        $this->composer = $composer;
    }
    public function supports(Type $type)
    {
        return ($type instanceof ComplexType);
    }
    public function encode($variable, \DOMNode $node, Type $type)
    {
        if ($type instanceof AbstractComplexType) {

            foreach ($type->getAttributes() as $attribute) {

                $val  = self::getValueFrom($variable, $attribute->getName());

                if ($val!==null) {
                    if ($attribute->getQualification()=="qualified") {
                        $attributeNode = $node->ownerDocument->ocreateAttributeNS($attribute->getNs(), $node->getPrefixFor($attribute->getNs()).":".$attribute->getName());
                        $node->setAttributeNodeNs($attributeNode);
                    } else {
                        $attributeNode = $node->ownerDocument->createAttribute($attribute->getName());
                        $node->setAttributeNode($attributeNode);
                    }
                    $this->convertSimplePhpXml($val, $attributeNode, $attribute->getType());

                } elseif ($attribute->isRequred()) {
                    throw new \Exception("Type $type, attributo ".$attribute." non deve essere vuoto");
                }
            }

            if ($type->getBase()) {
                $this->encode($variable, $node, $type->getBase());
            }

            if ($type instanceof ComplexType) {

                foreach ($type->getElements() as $element) {

                    $elementQualified = $element->getQualification()=="qualified";
                    $newType = $element->getType();

                    if($element->getMax()>1 &&
                            ($val = self::tryGetValueFrom($variable, $element->getName()))
                            && (is_array($val) || $val instanceof \Traversable)

                    ){
                        foreach ($val as $nval) {
                            if ($elementQualified) {
                                $newNode = $node->addPrefixedChild($element->getNs(), $element->getName());
                            } else {
                                $newNode = $node->addChild($element->getName());
                            }
                            $this->encode($nval, $newNode, $newType);
                        }
                    } elseif ($element->getMax()>1 && (is_array($variable) || $variable instanceof \Traversable)) {

                        foreach ($variable as $nval) {
                            if ($elementQualified) {
                                $newNode = $node->addPrefixedChild($element->getNs(), $element->getName());
                            } else {
                                $newNode = $node->addChild($element->getName());
                            }
                            $this->encode($nval, $newNode, $newType);
                        }

                    } else {

                        $val  = self::getValueFrom($variable, $element->getName());

                        if ($val!==null || $element->isNillable()) {

                            if ($elementQualified) {
                                $newNode = $node->addPrefixedChild($element->getNs(), $element->getName());
                            } else {
                                $newNode = $node->addChild($element->getName());
                            }
                            if ($val===null) {
                                $newNode->setAttributeNS(self::NS_XSI, $newNode->getPrefixFor(self::NS_XSI).":nil", "true");
                            } else {
                                $this->encode($val, $newNode, $newType);
                            }

                        } elseif ($element->getMin()>0) {
                            throw new \Exception($element." non deve essere vuoto");
                        }
                    }
                }
            }
        }
        if ($type instanceof SimpleType) {
            $this->convertSimplePhpXml($variable, $node, $type);
        }
    }

    protected function convertSimplePhpXml($value, $node, SimpleType $xsd)
    {
        try {
            $this->composer->encode($value, $node, $xsd);
        } catch (ConversionNotFoundException $e) {
            $base = $xsd->getBase();
            if ($base) {
                $this->composer->encode($value, $node, $base);
            } else {
                throw $e;
            }
        }
    }
}
