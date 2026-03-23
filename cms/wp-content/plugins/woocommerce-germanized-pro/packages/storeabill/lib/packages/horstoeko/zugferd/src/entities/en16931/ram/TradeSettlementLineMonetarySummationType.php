<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\ram;

/**
 * Class representing TradeSettlementLineMonetarySummationType
 *
 * XSD Type: TradeSettlementLineMonetarySummationType
 */
class TradeSettlementLineMonetarySummationType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\AmountType $lineTotalAmount
     */
    private $lineTotalAmount = null;

    /**
     * Gets as lineTotalAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\AmountType
     */
    public function getLineTotalAmount()
    {
        return $this->lineTotalAmount;
    }

    /**
     * Sets a new lineTotalAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\AmountType $lineTotalAmount
     * @return self
     */
    public function setLineTotalAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\AmountType $lineTotalAmount)
    {
        $this->lineTotalAmount = $lineTotalAmount;
        return $this;
    }
}
