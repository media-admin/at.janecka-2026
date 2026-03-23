<?php
namespace Vendidero\StoreaBill\Vendor\Einvoicing\Presets;

use Vendidero\StoreaBill\Vendor\Einvoicing\Invoice;

abstract class AbstractPreset {
    /**
     * Get specification identifier
     * @return string Specification identifier
     */
    abstract public function getSpecification(): string;


    /**
     * Get additional validation rules
     * @return array<string,callable> Map of rules
     */
    public function getRules(): array {
        return [];
    }


    /**
     * Setup invoice
     * @param Invoice $invoice Invoice instance
     */
    public function setupInvoice(Invoice $invoice) {
        $invoice->setRoundingMatrix(['' => 2]);
    }
}
