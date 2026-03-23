<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\ram;

/**
 * Class representing DocumentContextParameterType
 *
 * XSD Type: DocumentContextParameterType
 */
class DocumentContextParameterType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iD
     */
    private $iD = null;

    /**
     * Gets as iD
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType
     */
    public function getID()
    {
        return $this->iD;
    }

    /**
     * Sets a new iD
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iD
     * @return self
     */
    public function setID(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iD)
    {
        $this->iD = $iD;
        return $this;
    }
}
