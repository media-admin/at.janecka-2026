<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\ram;

/**
 * Class representing DebtorFinancialAccountType
 *
 * XSD Type: DebtorFinancialAccountType
 */
class DebtorFinancialAccountType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iBANID
     */
    private $iBANID = null;

    /**
     * Gets as iBANID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType
     */
    public function getIBANID()
    {
        return $this->iBANID;
    }

    /**
     * Sets a new iBANID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iBANID
     * @return self
     */
    public function setIBANID(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iBANID)
    {
        $this->iBANID = $iBANID;
        return $this;
    }
}
