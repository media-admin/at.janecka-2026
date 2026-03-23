<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\ram;

/**
 * Class representing ReferencedDocumentType
 *
 * XSD Type: ReferencedDocumentType
 */
class ReferencedDocumentType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $issuerAssignedID
     */
    private $issuerAssignedID = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $uRIID
     */
    private $uRIID = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $lineID
     */
    private $lineID = null;

    /**
     * @var string $typeCode
     */
    private $typeCode = null;

    /**
     * @var string $name
     */
    private $name = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\BinaryObjectType $attachmentBinaryObject
     */
    private $attachmentBinaryObject = null;

    /**
     * @var string $referenceTypeCode
     */
    private $referenceTypeCode = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\qdt\FormattedDateTimeType $formattedIssueDateTime
     */
    private $formattedIssueDateTime = null;

    /**
     * Gets as issuerAssignedID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType
     */
    public function getIssuerAssignedID()
    {
        return $this->issuerAssignedID;
    }

    /**
     * Sets a new issuerAssignedID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $issuerAssignedID
     * @return self
     */
    public function setIssuerAssignedID(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $issuerAssignedID = null)
    {
        $this->issuerAssignedID = $issuerAssignedID;
        return $this;
    }

    /**
     * Gets as uRIID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType
     */
    public function getURIID()
    {
        return $this->uRIID;
    }

    /**
     * Sets a new uRIID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $uRIID
     * @return self
     */
    public function setURIID(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $uRIID = null)
    {
        $this->uRIID = $uRIID;
        return $this;
    }

    /**
     * Gets as lineID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType
     */
    public function getLineID()
    {
        return $this->lineID;
    }

    /**
     * Sets a new lineID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $lineID
     * @return self
     */
    public function setLineID(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\IDType $lineID = null)
    {
        $this->lineID = $lineID;
        return $this;
    }

    /**
     * Gets as typeCode
     *
     * @return string
     */
    public function getTypeCode()
    {
        return $this->typeCode;
    }

    /**
     * Sets a new typeCode
     *
     * @param  string $typeCode
     * @return self
     */
    public function setTypeCode($typeCode)
    {
        $this->typeCode = $typeCode;
        return $this;
    }

    /**
     * Gets as name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets a new name
     *
     * @param  string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets as attachmentBinaryObject
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\BinaryObjectType
     */
    public function getAttachmentBinaryObject()
    {
        return $this->attachmentBinaryObject;
    }

    /**
     * Sets a new attachmentBinaryObject
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\BinaryObjectType $attachmentBinaryObject
     * @return self
     */
    public function setAttachmentBinaryObject(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\udt\BinaryObjectType $attachmentBinaryObject = null)
    {
        $this->attachmentBinaryObject = $attachmentBinaryObject;
        return $this;
    }

    /**
     * Gets as referenceTypeCode
     *
     * @return string
     */
    public function getReferenceTypeCode()
    {
        return $this->referenceTypeCode;
    }

    /**
     * Sets a new referenceTypeCode
     *
     * @param  string $referenceTypeCode
     * @return self
     */
    public function setReferenceTypeCode($referenceTypeCode)
    {
        $this->referenceTypeCode = $referenceTypeCode;
        return $this;
    }

    /**
     * Gets as formattedIssueDateTime
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\qdt\FormattedDateTimeType
     */
    public function getFormattedIssueDateTime()
    {
        return $this->formattedIssueDateTime;
    }

    /**
     * Sets a new formattedIssueDateTime
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\qdt\FormattedDateTimeType $formattedIssueDateTime
     * @return self
     */
    public function setFormattedIssueDateTime(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\en16931\qdt\FormattedDateTimeType $formattedIssueDateTime = null)
    {
        $this->formattedIssueDateTime = $formattedIssueDateTime;
        return $this;
    }
}
