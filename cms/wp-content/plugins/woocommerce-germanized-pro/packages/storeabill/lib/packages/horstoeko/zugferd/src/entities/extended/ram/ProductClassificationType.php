<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram;

/**
 * Class representing ProductClassificationType
 *
 * XSD Type: ProductClassificationType
 */
class ProductClassificationType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType $classCode
     */
    private $classCode = null;

    /**
     * @var string $className
     */
    private $className = null;

    /**
     * Gets as classCode
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType
     */
    public function getClassCode()
    {
        return $this->classCode;
    }

    /**
     * Sets a new classCode
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType $classCode
     * @return self
     */
    public function setClassCode(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType $classCode = null)
    {
        $this->classCode = $classCode;
        return $this;
    }

    /**
     * Gets as className
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Sets a new className
     *
     * @param  string $className
     * @return self
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }
}
