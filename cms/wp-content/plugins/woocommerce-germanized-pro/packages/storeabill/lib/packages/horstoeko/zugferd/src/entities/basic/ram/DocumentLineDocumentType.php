<?php

namespace Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram;

/**
 * Class representing DocumentLineDocumentType
 *
 * XSD Type: DocumentLineDocumentType
 */
class DocumentLineDocumentType
{

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType $lineID
     */
    private $lineID = null;

    /**
     * @var \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\NoteType $includedNote
     */
    private $includedNote = null;

    /**
     * Gets as lineID
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType
     */
    public function getLineID()
    {
        return $this->lineID;
    }

    /**
     * Sets a new lineID
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType $lineID
     * @return self
     */
    public function setLineID(\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\udt\IDType $lineID)
    {
        $this->lineID = $lineID;
        return $this;
    }

    /**
     * Gets as includedNote
     *
     * @return \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\NoteType
     */
    public function getIncludedNote()
    {
        return $this->includedNote;
    }

    /**
     * Sets a new includedNote
     *
     * @param  \Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\NoteType $includedNote
     * @return self
     */
    public function setIncludedNote(?\Vendidero\StoreaBill\Vendor\horstoeko\zugferd\entities\basic\ram\NoteType $includedNote = null)
    {
        $this->includedNote = $includedNote;
        return $this;
    }
}
