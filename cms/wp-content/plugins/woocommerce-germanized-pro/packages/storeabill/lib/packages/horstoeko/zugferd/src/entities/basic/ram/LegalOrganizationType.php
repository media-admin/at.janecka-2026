<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram;

/**
 * Class representing LegalOrganizationType
 *
 * XSD Type: LegalOrganizationType
 */
class LegalOrganizationType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType $iD
     */
    private $iD = null;

    /**
     * @var string $tradingBusinessName
     */
    private $tradingBusinessName = null;

    /**
     * Gets as iD
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType
     */
    public function getID()
    {
        return $this->iD;
    }

    /**
     * Sets a new iD
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType $iD
     * @return self
     */
    public function setID(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType $iD = null)
    {
        $this->iD = $iD;
        return $this;
    }

    /**
     * Gets as tradingBusinessName
     *
     * @return string
     */
    public function getTradingBusinessName()
    {
        return $this->tradingBusinessName;
    }

    /**
     * Sets a new tradingBusinessName
     *
     * @param  string $tradingBusinessName
     * @return self
     */
    public function setTradingBusinessName($tradingBusinessName)
    {
        $this->tradingBusinessName = $tradingBusinessName;
        return $this;
    }
}
