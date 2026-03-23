<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\ram;

/**
 * Class representing TradeSettlementHeaderMonetarySummationType
 *
 * XSD Type: TradeSettlementHeaderMonetarySummationType
 */
class TradeSettlementHeaderMonetarySummationType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $taxBasisTotalAmount
     */
    private $taxBasisTotalAmount = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType[] $taxTotalAmount
     */
    private $taxTotalAmount = [
        
    ];

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $grandTotalAmount
     */
    private $grandTotalAmount = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $duePayableAmount
     */
    private $duePayableAmount = null;

    /**
     * Gets as taxBasisTotalAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType
     */
    public function getTaxBasisTotalAmount()
    {
        return $this->taxBasisTotalAmount;
    }

    /**
     * Sets a new taxBasisTotalAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $taxBasisTotalAmount
     * @return self
     */
    public function setTaxBasisTotalAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $taxBasisTotalAmount)
    {
        $this->taxBasisTotalAmount = $taxBasisTotalAmount;
        return $this;
    }

    /**
     * Adds as taxTotalAmount
     *
     * @return self
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $taxTotalAmount
     */
    public function addToTaxTotalAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $taxTotalAmount)
    {
        $this->taxTotalAmount[] = $taxTotalAmount;
        return $this;
    }

    /**
     * isset taxTotalAmount
     *
     * @param  int|string $index
     * @return bool
     */
    public function issetTaxTotalAmount($index)
    {
        return isset($this->taxTotalAmount[$index]);
    }

    /**
     * unset taxTotalAmount
     *
     * @param  int|string $index
     * @return void
     */
    public function unsetTaxTotalAmount($index)
    {
        unset($this->taxTotalAmount[$index]);
    }

    /**
     * Gets as taxTotalAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType[]
     */
    public function getTaxTotalAmount()
    {
        return $this->taxTotalAmount;
    }

    /**
     * Sets a new taxTotalAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType[] $taxTotalAmount
     * @return self
     */
    public function setTaxTotalAmount(?array $taxTotalAmount = null)
    {
        $this->taxTotalAmount = $taxTotalAmount;
        return $this;
    }

    /**
     * Gets as grandTotalAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType
     */
    public function getGrandTotalAmount()
    {
        return $this->grandTotalAmount;
    }

    /**
     * Sets a new grandTotalAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $grandTotalAmount
     * @return self
     */
    public function setGrandTotalAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $grandTotalAmount)
    {
        $this->grandTotalAmount = $grandTotalAmount;
        return $this;
    }

    /**
     * Gets as duePayableAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType
     */
    public function getDuePayableAmount()
    {
        return $this->duePayableAmount;
    }

    /**
     * Sets a new duePayableAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $duePayableAmount
     * @return self
     */
    public function setDuePayableAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\minimum\udt\AmountType $duePayableAmount)
    {
        $this->duePayableAmount = $duePayableAmount;
        return $this;
    }
}
