<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram;

/**
 * Class representing SupplyChainTradeTransactionType
 *
 * XSD Type: SupplyChainTradeTransactionType
 */
class SupplyChainTradeTransactionType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\SupplyChainTradeLineItemType[] $includedSupplyChainTradeLineItem
     */
    private $includedSupplyChainTradeLineItem = [
        
    ];

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeAgreementType $applicableHeaderTradeAgreement
     */
    private $applicableHeaderTradeAgreement = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeDeliveryType $applicableHeaderTradeDelivery
     */
    private $applicableHeaderTradeDelivery = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeSettlementType $applicableHeaderTradeSettlement
     */
    private $applicableHeaderTradeSettlement = null;

    /**
     * Adds as includedSupplyChainTradeLineItem
     *
     * @return self
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\SupplyChainTradeLineItemType $includedSupplyChainTradeLineItem
     */
    public function addToIncludedSupplyChainTradeLineItem(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\SupplyChainTradeLineItemType $includedSupplyChainTradeLineItem)
    {
        $this->includedSupplyChainTradeLineItem[] = $includedSupplyChainTradeLineItem;
        return $this;
    }

    /**
     * isset includedSupplyChainTradeLineItem
     *
     * @param  int|string $index
     * @return bool
     */
    public function issetIncludedSupplyChainTradeLineItem($index)
    {
        return isset($this->includedSupplyChainTradeLineItem[$index]);
    }

    /**
     * unset includedSupplyChainTradeLineItem
     *
     * @param  int|string $index
     * @return void
     */
    public function unsetIncludedSupplyChainTradeLineItem($index)
    {
        unset($this->includedSupplyChainTradeLineItem[$index]);
    }

    /**
     * Gets as includedSupplyChainTradeLineItem
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\SupplyChainTradeLineItemType[]
     */
    public function getIncludedSupplyChainTradeLineItem()
    {
        return $this->includedSupplyChainTradeLineItem;
    }

    /**
     * Sets a new includedSupplyChainTradeLineItem
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\SupplyChainTradeLineItemType[] $includedSupplyChainTradeLineItem
     * @return self
     */
    public function setIncludedSupplyChainTradeLineItem(array $includedSupplyChainTradeLineItem)
    {
        $this->includedSupplyChainTradeLineItem = $includedSupplyChainTradeLineItem;
        return $this;
    }

    /**
     * Gets as applicableHeaderTradeAgreement
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeAgreementType
     */
    public function getApplicableHeaderTradeAgreement()
    {
        return $this->applicableHeaderTradeAgreement;
    }

    /**
     * Sets a new applicableHeaderTradeAgreement
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeAgreementType $applicableHeaderTradeAgreement
     * @return self
     */
    public function setApplicableHeaderTradeAgreement(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeAgreementType $applicableHeaderTradeAgreement)
    {
        $this->applicableHeaderTradeAgreement = $applicableHeaderTradeAgreement;
        return $this;
    }

    /**
     * Gets as applicableHeaderTradeDelivery
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeDeliveryType
     */
    public function getApplicableHeaderTradeDelivery()
    {
        return $this->applicableHeaderTradeDelivery;
    }

    /**
     * Sets a new applicableHeaderTradeDelivery
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeDeliveryType $applicableHeaderTradeDelivery
     * @return self
     */
    public function setApplicableHeaderTradeDelivery(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeDeliveryType $applicableHeaderTradeDelivery)
    {
        $this->applicableHeaderTradeDelivery = $applicableHeaderTradeDelivery;
        return $this;
    }

    /**
     * Gets as applicableHeaderTradeSettlement
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeSettlementType
     */
    public function getApplicableHeaderTradeSettlement()
    {
        return $this->applicableHeaderTradeSettlement;
    }

    /**
     * Sets a new applicableHeaderTradeSettlement
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeSettlementType $applicableHeaderTradeSettlement
     * @return self
     */
    public function setApplicableHeaderTradeSettlement(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\HeaderTradeSettlementType $applicableHeaderTradeSettlement)
    {
        $this->applicableHeaderTradeSettlement = $applicableHeaderTradeSettlement;
        return $this;
    }
}
