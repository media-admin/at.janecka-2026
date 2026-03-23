<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram;

/**
 * Class representing AdvancePaymentType
 *
 * XSD Type: AdvancePaymentType
 */
class AdvancePaymentType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\AmountType $paidAmount
     */
    private $paidAmount = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\qdt\FormattedDateTimeType $formattedReceivedDateTime
     */
    private $formattedReceivedDateTime = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\TradeTaxType[] $includedTradeTax
     */
    private $includedTradeTax = [
        
    ];

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\ReferencedDocumentType $invoiceSpecifiedReferencedDocument
     */
    private $invoiceSpecifiedReferencedDocument = null;

    /**
     * Gets as paidAmount
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\AmountType
     */
    public function getPaidAmount()
    {
        return $this->paidAmount;
    }

    /**
     * Sets a new paidAmount
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\AmountType $paidAmount
     * @return self
     */
    public function setPaidAmount(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\udt\AmountType $paidAmount)
    {
        $this->paidAmount = $paidAmount;
        return $this;
    }

    /**
     * Gets as formattedReceivedDateTime
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\qdt\FormattedDateTimeType
     */
    public function getFormattedReceivedDateTime()
    {
        return $this->formattedReceivedDateTime;
    }

    /**
     * Sets a new formattedReceivedDateTime
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\qdt\FormattedDateTimeType $formattedReceivedDateTime
     * @return self
     */
    public function setFormattedReceivedDateTime(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\qdt\FormattedDateTimeType $formattedReceivedDateTime = null)
    {
        $this->formattedReceivedDateTime = $formattedReceivedDateTime;
        return $this;
    }

    /**
     * Adds as includedTradeTax
     *
     * @return self
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\TradeTaxType $includedTradeTax
     */
    public function addToIncludedTradeTax(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\TradeTaxType $includedTradeTax)
    {
        $this->includedTradeTax[] = $includedTradeTax;
        return $this;
    }

    /**
     * isset includedTradeTax
     *
     * @param  int|string $index
     * @return bool
     */
    public function issetIncludedTradeTax($index)
    {
        return isset($this->includedTradeTax[$index]);
    }

    /**
     * unset includedTradeTax
     *
     * @param  int|string $index
     * @return void
     */
    public function unsetIncludedTradeTax($index)
    {
        unset($this->includedTradeTax[$index]);
    }

    /**
     * Gets as includedTradeTax
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\TradeTaxType[]
     */
    public function getIncludedTradeTax()
    {
        return $this->includedTradeTax;
    }

    /**
     * Sets a new includedTradeTax
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\TradeTaxType[] $includedTradeTax
     * @return self
     */
    public function setIncludedTradeTax(array $includedTradeTax)
    {
        $this->includedTradeTax = $includedTradeTax;
        return $this;
    }

    /**
     * Gets as invoiceSpecifiedReferencedDocument
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\ReferencedDocumentType
     */
    public function getInvoiceSpecifiedReferencedDocument()
    {
        return $this->invoiceSpecifiedReferencedDocument;
    }

    /**
     * Sets a new invoiceSpecifiedReferencedDocument
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\ReferencedDocumentType $invoiceSpecifiedReferencedDocument
     * @return self
     */
    public function setInvoiceSpecifiedReferencedDocument(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\extended\ram\ReferencedDocumentType $invoiceSpecifiedReferencedDocument = null)
    {
        $this->invoiceSpecifiedReferencedDocument = $invoiceSpecifiedReferencedDocument;
        return $this;
    }
}
