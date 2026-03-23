<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\ram;

/**
 * Class representing CreditorFinancialAccountType
 *
 * XSD Type: CreditorFinancialAccountType
 */
class CreditorFinancialAccountType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iBANID
     */
    private $iBANID = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $proprietaryID
     */
    private $proprietaryID = null;

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
    public function setIBANID(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $iBANID = null)
    {
        $this->iBANID = $iBANID;
        return $this;
    }

    /**
     * Gets as proprietaryID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType
     */
    public function getProprietaryID()
    {
        return $this->proprietaryID;
    }

    /**
     * Sets a new proprietaryID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $proprietaryID
     * @return self
     */
    public function setProprietaryID(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basicwl\udt\IDType $proprietaryID = null)
    {
        $this->proprietaryID = $proprietaryID;
        return $this;
    }
}
