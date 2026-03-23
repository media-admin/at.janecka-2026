<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\ram;

/**
 * Class representing LineTradeDeliveryType
 *
 * XSD Type: LineTradeDeliveryType
 */
class LineTradeDeliveryType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\QuantityType $billedQuantity
     */
    private $billedQuantity = null;

    /**
     * Gets as billedQuantity
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\QuantityType
     */
    public function getBilledQuantity()
    {
        return $this->billedQuantity;
    }

    /**
     * Sets a new billedQuantity
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\QuantityType $billedQuantity
     * @return self
     */
    public function setBilledQuantity(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\QuantityType $billedQuantity)
    {
        $this->billedQuantity = $billedQuantity;
        return $this;
    }
}
