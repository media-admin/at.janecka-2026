<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram;

/**
 * Class representing CreditorFinancialInstitutionType
 *
 * XSD Type: CreditorFinancialInstitutionType
 */
class CreditorFinancialInstitutionType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\IDType $bICID
     */
    private $bICID = null;

    /**
     * Gets as bICID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\IDType
     */
    public function getBICID()
    {
        return $this->bICID;
    }

    /**
     * Sets a new bICID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\IDType $bICID
     * @return self
     */
    public function setBICID(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\IDType $bICID)
    {
        $this->bICID = $bICID;
        return $this;
    }
}
