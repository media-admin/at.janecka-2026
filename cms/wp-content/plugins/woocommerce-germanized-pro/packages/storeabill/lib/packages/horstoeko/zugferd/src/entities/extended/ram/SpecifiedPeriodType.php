<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram;

/**
 * Class representing SpecifiedPeriodType
 *
 * XSD Type: SpecifiedPeriodType
 */
class SpecifiedPeriodType
{

    /**
     * @var string $description
     */
    private $description = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $startDateTime
     */
    private $startDateTime = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $endDateTime
     */
    private $endDateTime = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $completeDateTime
     */
    private $completeDateTime = null;

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
     * Gets as startDateTime
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Sets a new startDateTime
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $startDateTime
     * @return self
     */
    public function setStartDateTime(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $startDateTime = null)
    {
        $this->startDateTime = $startDateTime;
        return $this;
    }

    /**
     * Gets as endDateTime
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * Sets a new endDateTime
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $endDateTime
     * @return self
     */
    public function setEndDateTime(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $endDateTime = null)
    {
        $this->endDateTime = $endDateTime;
        return $this;
    }

    /**
     * Gets as completeDateTime
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType
     */
    public function getCompleteDateTime()
    {
        return $this->completeDateTime;
    }

    /**
     * Sets a new completeDateTime
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $completeDateTime
     * @return self
     */
    public function setCompleteDateTime(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\DateTimeType $completeDateTime = null)
    {
        $this->completeDateTime = $completeDateTime;
        return $this;
    }
}
