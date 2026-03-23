<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram;

/**
 * Class representing LineTradeSettlementType
 *
 * XSD Type: LineTradeSettlementType
 */
class LineTradeSettlementType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeTaxType $applicableTradeTax
     */
    private $applicableTradeTax = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\SpecifiedPeriodType $billingSpecifiedPeriod
     */
    private $billingSpecifiedPeriod = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType[] $specifiedTradeAllowanceCharge
     */
    private $specifiedTradeAllowanceCharge = [
        
    ];

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeSettlementLineMonetarySummationType $specifiedTradeSettlementLineMonetarySummation
     */
    private $specifiedTradeSettlementLineMonetarySummation = null;

    /**
     * Gets as applicableTradeTax
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeTaxType
     */
    public function getApplicableTradeTax()
    {
        return $this->applicableTradeTax;
    }

    /**
     * Sets a new applicableTradeTax
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeTaxType $applicableTradeTax
     * @return self
     */
    public function setApplicableTradeTax(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeTaxType $applicableTradeTax)
    {
        $this->applicableTradeTax = $applicableTradeTax;
        return $this;
    }

    /**
     * Gets as billingSpecifiedPeriod
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\SpecifiedPeriodType
     */
    public function getBillingSpecifiedPeriod()
    {
        return $this->billingSpecifiedPeriod;
    }

    /**
     * Sets a new billingSpecifiedPeriod
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\SpecifiedPeriodType $billingSpecifiedPeriod
     * @return self
     */
    public function setBillingSpecifiedPeriod(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\SpecifiedPeriodType $billingSpecifiedPeriod = null)
    {
        $this->billingSpecifiedPeriod = $billingSpecifiedPeriod;
        return $this;
    }

    /**
     * Adds as specifiedTradeAllowanceCharge
     *
     * @return self
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType $specifiedTradeAllowanceCharge
     */
    public function addToSpecifiedTradeAllowanceCharge(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType $specifiedTradeAllowanceCharge)
    {
        $this->specifiedTradeAllowanceCharge[] = $specifiedTradeAllowanceCharge;
        return $this;
    }

    /**
     * isset specifiedTradeAllowanceCharge
     *
     * @param  int|string $index
     * @return bool
     */
    public function issetSpecifiedTradeAllowanceCharge($index)
    {
        return isset($this->specifiedTradeAllowanceCharge[$index]);
    }

    /**
     * unset specifiedTradeAllowanceCharge
     *
     * @param  int|string $index
     * @return void
     */
    public function unsetSpecifiedTradeAllowanceCharge($index)
    {
        unset($this->specifiedTradeAllowanceCharge[$index]);
    }

    /**
     * Gets as specifiedTradeAllowanceCharge
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType[]
     */
    public function getSpecifiedTradeAllowanceCharge()
    {
        return $this->specifiedTradeAllowanceCharge;
    }

    /**
     * Sets a new specifiedTradeAllowanceCharge
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType[] $specifiedTradeAllowanceCharge
     * @return self
     */
    public function setSpecifiedTradeAllowanceCharge(?array $specifiedTradeAllowanceCharge = null)
    {
        $this->specifiedTradeAllowanceCharge = $specifiedTradeAllowanceCharge;
        return $this;
    }

    /**
     * Gets as specifiedTradeSettlementLineMonetarySummation
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeSettlementLineMonetarySummationType
     */
    public function getSpecifiedTradeSettlementLineMonetarySummation()
    {
        return $this->specifiedTradeSettlementLineMonetarySummation;
    }

    /**
     * Sets a new specifiedTradeSettlementLineMonetarySummation
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeSettlementLineMonetarySummationType $specifiedTradeSettlementLineMonetarySummation
     * @return self
     */
    public function setSpecifiedTradeSettlementLineMonetarySummation(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeSettlementLineMonetarySummationType $specifiedTradeSettlementLineMonetarySummation)
    {
        $this->specifiedTradeSettlementLineMonetarySummation = $specifiedTradeSettlementLineMonetarySummation;
        return $this;
    }
}
