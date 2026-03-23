<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\ram;

/**
 * Class representing ProductClassificationType
 *
 * XSD Type: ProductClassificationType
 */
class ProductClassificationType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\CodeType $classCode
     */
    private $classCode = null;

    /**
     * Gets as classCode
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\CodeType
     */
    public function getClassCode()
    {
        return $this->classCode;
    }

    /**
     * Sets a new classCode
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\CodeType $classCode
     * @return self
     */
    public function setClassCode(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\CodeType $classCode = null)
    {
        $this->classCode = $classCode;
        return $this;
    }
}
