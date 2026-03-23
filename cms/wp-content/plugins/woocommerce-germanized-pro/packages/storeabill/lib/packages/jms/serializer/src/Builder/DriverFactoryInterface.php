<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Builder;

use Doctrine\Common\Annotations\Reader;
use Vendidero\StoreaBill\Vendor\Metadata\Driver\DriverInterface;

interface DriverFactoryInterface
{
    public function createDriver(array $metadataDirs, ?Reader $annotationReader = null): DriverInterface;
}
