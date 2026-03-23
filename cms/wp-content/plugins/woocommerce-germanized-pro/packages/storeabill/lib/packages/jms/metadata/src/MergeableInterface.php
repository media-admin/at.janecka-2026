<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\Metadata;

interface MergeableInterface
{
    public function merge(MergeableInterface $object): void;
}
