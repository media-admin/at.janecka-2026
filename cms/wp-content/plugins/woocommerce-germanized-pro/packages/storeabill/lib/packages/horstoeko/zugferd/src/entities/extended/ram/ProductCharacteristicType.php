<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram;

/**
 * Class representing ProductCharacteristicType
 *
 * XSD Type: ProductCharacteristicType
 */
class ProductCharacteristicType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType $typeCode
     */
    private $typeCode = null;

    /**
     * @var string $description
     */
    private $description = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\MeasureType $valueMeasure
     */
    private $valueMeasure = null;

    /**
     * @var string $value
     */
    private $value = null;

    /**
     * Gets as typeCode
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType
     */
    public function getTypeCode()
    {
        return $this->typeCode;
    }

    /**
     * Sets a new typeCode
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType $typeCode
     * @return self
     */
    public function setTypeCode(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\CodeType $typeCode = null)
    {
        $this->typeCode = $typeCode;
        return $this;
    }

    /**
     * Gets as description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets a new description
     *
     * @param  string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Gets as valueMeasure
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\MeasureType
     */
    public function getValueMeasure()
    {
        return $this->valueMeasure;
    }

    /**
     * Sets a new valueMeasure
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\MeasureType $valueMeasure
     * @return self
     */
    public function setValueMeasure(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\MeasureType $valueMeasure = null)
    {
        $this->valueMeasure = $valueMeasure;
        return $this;
    }

    /**
     * Gets as value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets a new value
     *
     * @param  string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
