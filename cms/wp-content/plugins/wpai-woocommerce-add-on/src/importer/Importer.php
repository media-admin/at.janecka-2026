<?php

namespace wpai_woocommerce_add_on\importer;

abstract class Importer extends ImportBase implements ImporterInterface {

    /**
     * @return ImporterIndex
     */
    public function getIndexObject() {
        return $this->index;
    }
}