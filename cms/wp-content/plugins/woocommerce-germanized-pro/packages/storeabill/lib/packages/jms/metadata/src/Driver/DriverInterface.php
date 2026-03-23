<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\Metadata\Driver;

use Vendidero\StoreaBill\Vendor\Metadata\ClassMetadata;

interface DriverInterface
{
    public function loadMetadataForClass(\ReflectionClass $class): ?ClassMetadata;
}
