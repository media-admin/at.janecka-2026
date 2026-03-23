<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram;

/**
 * Class representing TradePriceType
 *
 * XSD Type: TradePriceType
 */
class TradePriceType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\AmountType $chargeAmount
     */
    private $chargeAmount = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\QuantityType $basisQuantity
     */
    private $basisQuantity = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType $appliedTradeAllowanceCharge
     */
    private $appliedTradeAllowanceCharge = null;

    /**
     * Gets as chargeAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\AmountType
     */
    public function getChargeAmount()
    {
        return $this->chargeAmount;
    }

    /**
     * Sets a new chargeAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\AmountType $chargeAmount
     * @return self
     */
    public function setChargeAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\AmountType $chargeAmount)
    {
        $this->chargeAmount = $chargeAmount;
        return $this;
    }

    /**
     * Gets as basisQuantity
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\QuantityType
     */
    public function getBasisQuantity()
    {
        return $this->basisQuantity;
    }

    /**
     * Sets a new basisQuantity
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\QuantityType $basisQuantity
     * @return self
     */
    public function setBasisQuantity(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\QuantityType $basisQuantity = null)
    {
        $this->basisQuantity = $basisQuantity;
        return $this;
    }

    /**
     * Gets as appliedTradeAllowanceCharge
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType
     */
    public function getAppliedTradeAllowanceCharge()
    {
        return $this->appliedTradeAllowanceCharge;
    }

    /**
     * Sets a new appliedTradeAllowanceCharge
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType $appliedTradeAllowanceCharge
     * @return self
     */
    public function setAppliedTradeAllowanceCharge(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\TradeAllowanceChargeType $appliedTradeAllowanceCharge = null)
    {
        $this->appliedTradeAllowanceCharge = $appliedTradeAllowanceCharge;
        return $this;
    }
}
